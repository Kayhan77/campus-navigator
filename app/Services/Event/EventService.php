<?php

namespace App\Services\Event;

use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\UpdateEventDTO;
use App\Exceptions\ApiException;
use App\Filters\EventFilter;
use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use App\Services\Cache\CacheTags;
use App\Services\FirebaseService;
use App\Services\Search\SearchCacheService;
use App\Services\SupabaseStorageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class EventService
{
    private const IMAGE_PATH = 'events';

    public function __construct(
        private readonly SearchCacheService $cache,
        private readonly FirebaseService $firebase,
        private readonly SupabaseStorageService $supabaseStorage
    ) {}

    // -------------------------------------------------------------------------
    // Listings
    // -------------------------------------------------------------------------

    /**
     * Filtered, cached, paginated list for the public API.
     * Cache is tagged with CacheTags::EVENTS; EventObserver flushes it
     * automatically on any create / update / delete.
     */
    public function listPaginated(
        EventFilter $filter,
        Request $request,
        int $perPage
    ): LengthAwarePaginator {
        $key = SearchCacheService::buildKey(
            CacheTags::EVENTS,
            $filter,
            (int) $request->input('page', 1),
            $perPage,
        );

        return $this->cache->remember(
            CacheTags::EVENTS,
            $key,
            fn () => Event::filter($filter)
                ->withAllowed($request, [])
                ->paginate($perPage)
        );
    }

    /**
     * Admin-only paginated list — includes room relation, no filter pipeline.
     */
    public function listAdminPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Event::with('room')->latest()->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // Single record
    // -------------------------------------------------------------------------

    public function getById(Event $event): Event
    {
        return $event;
    }

    // -------------------------------------------------------------------------
    // Writes  (cache invalidation handled by EventObserver)
    // -------------------------------------------------------------------------

    /**
     * Register a user to an event with race-condition-safe capacity checks.
     * Uses atomic increment on persisted registered_users_count column.
     */
    public function registerUserToEvent(Event $event, User $user): void
    {
        try {
            DB::transaction(function () use ($event, $user): void {
                /** @var Event $lockedEvent */
                $lockedEvent = Event::query()
                    ->whereKey($event->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedEvent->registration_required) {
                    throw new ApiException('Registration is not required for this event.', 422);
                }

                $alreadyRegistered = $lockedEvent->registeredUsers()
                    ->whereKey($user->id)
                    ->exists();

                if ($alreadyRegistered) {
                    return;
                }

                // null max_attendees means unlimited capacity.
                if ($lockedEvent->max_attendees !== null) {
                    if ($lockedEvent->registered_users_count >= $lockedEvent->max_attendees) {
                        throw new ApiException('Event is fully booked', 422);
                    }
                }

                $lockedEvent->registeredUsers()->syncWithoutDetaching([$user->id]);
                $lockedEvent->increment('registered_users_count');
            });

            $this->cache->invalidate(CacheTags::EVENTS);
        } catch (ApiException|ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logServiceError('event_register_failed', $e, [
                'event_id' => $event->id,
                'user_id' => $user->id,
            ]);

            throw new ApiException('Failed to register to event.', 500);
        }
    }

    /**
     * Unregister a user from an event.
     * Uses atomic decrement on persisted registered_users_count column.
     * Idempotent: safe if user wasn't registered.
     */
    public function unregisterUserFromEvent(Event $event, User $user): void
    {
        try {
            DB::transaction(function () use ($event, $user): void {
                /** @var Event $lockedEvent */
                $lockedEvent = Event::query()
                    ->whereKey($event->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $wasRegistered = $lockedEvent->registeredUsers()
                    ->whereKey($user->id)
                    ->exists();

                if ($wasRegistered) {
                    $lockedEvent->registeredUsers()->detach($user->id);
                    // Ensure counter never goes below 0
                    if ($lockedEvent->registered_users_count > 0) {
                        $lockedEvent->decrement('registered_users_count');
                    }
                }
            });

            $this->cache->invalidate(CacheTags::EVENTS);
        } catch (ApiException|ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logServiceError('event_unregister_failed', $e, [
                'event_id' => $event->id,
                'user_id' => $user->id,
            ]);

            throw new ApiException('Failed to unregister from event.', 500);
        }
    }

    /**
     * Create event with image upload
     */
    public function create(CreateEventDTO $dto, int $userId, ?UploadedFile $image = null): Event
    {
        try {
            $this->validateCapacity($dto->room_id, $dto->max_attendees);

            $data = $dto->toArray();

            if ($image) {
                $data['image'] = $this->storeImage($image);
            }

            $event = Event::create(array_merge($data, ['created_by' => $userId]));

            $this->notifyUsers(
                title: 'New Event Created',
                body: $event->title,
                data: ['type' => 'event', 'id' => (string) $event->id]
            );

            return $event->fresh();
        } catch (ApiException|ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logServiceError('event_create_failed', $e, [
                'user_id' => $userId,
                'title' => $dto->title,
            ]);

            throw new ApiException('Failed to create event.', 500);
        }
    }

    /**
     * Update event with optional image replacement
     */
    public function update(Event $event, UpdateEventDTO $dto, ?UploadedFile $image = null): Event
    {
        try {
            $this->guardTerminalStatus($event);

            // Validate business constraint: cannot disable registration_required if users already registered
            if ($dto->registration_required === false && $event->registered_users_count > 0) {
                throw ValidationException::withMessages([
                    'registration_required' => 'Cannot disable registration requirement when users are already registered for this event.',
                ]);
            }

            $roomId       = $dto->room_id       ?? $event->room_id;
            $maxAttendees = $dto->max_attendees   ?? $event->max_attendees;
            $this->validateCapacity($roomId, $maxAttendees);

            $data = $dto->toArray();

            // Handle image replacement
            if ($image) {
                // Delete old image if exists
                if ($event->image) {
                    $this->deleteImage($event->image);
                }
                $data['image'] = $this->storeImage($image);
            }

            $event->update($data);
            $updated = $event->fresh();

            $this->notifyUsers(
                title: 'Event Updated',
                body: $updated->title,
                data: ['type' => 'event', 'id' => (string) $updated->id]
            );

            return $updated;
        } catch (ApiException|ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logServiceError('event_update_failed', $e, [
                'event_id' => $event->id,
                'user_id' => auth('api')->id(),
            ]);

            throw new ApiException('Failed to update event.', 500);
        }
    }

    /**
     * Delete event and its image
     */
    public function delete(Event $event): void
    {
        // Delete image from storage
        if ($event->image) {
            $this->deleteImage($event->image);
        }

        $event->delete();
    }

    // -------------------------------------------------------------------------
    // Image handling
    // -------------------------------------------------------------------------

    /**
     * Store uploaded image and return relative path
     */
    private function storeImage(UploadedFile $image): string
    {
        return $this->supabaseStorage->uploadImage($image, self::IMAGE_PATH);
    }

    /**
     * Delete image from storage safely
     */
    private function deleteImage(string $imagePath): void
    {
        $this->supabaseStorage->delete($imagePath);
    }

    // -------------------------------------------------------------------------
    // Business-rule helpers
    // -------------------------------------------------------------------------

    /**
     * Prevent max_attendees from exceeding the assigned room's capacity.
     */
    private function validateCapacity(?int $roomId, ?int $maxAttendees): void
    {
        if ($roomId === null || $maxAttendees === null) {
            return;
        }

        $room = Room::find($roomId);

        if ($room && $room->capacity > 0 && $maxAttendees > $room->capacity) {
            throw ValidationException::withMessages([
                'max_attendees' => "max_attendees ({$maxAttendees}) cannot exceed room capacity ({$room->capacity}).",
            ]);
        }
    }

    /**
     * Cancelled or completed events must not be mutated further.
     */
    private function guardTerminalStatus(Event $event): void
    {
        if (in_array($event->status, ['cancelled', 'completed'], true)) {
            throw ValidationException::withMessages([
                'status' => "A {$event->status} event cannot be updated.",
            ]);
        }
    }

    private function notifyUsers(string $title, string $body, array $data = []): void
    {
        User::query()
            ->whereHas('deviceTokens')
            ->with('deviceTokens:id,user_id,token')
            ->chunkById(200, function ($users) use ($title, $body, $data): void {
                foreach ($users as $user) {
                    try {
                        $this->firebase->sendToUser($user, $title, $body, $data);
                    } catch (Throwable $e) {
                        $this->logServiceError('event_notification_failed', $e, [
                            'recipient_user_id' => $user->id,
                            'title' => $title,
                        ]);
                    }
                }
            });
    }

    private function logServiceError(string $operation, Throwable $e, array $context = []): void
    {
        logger()->error('Event service operation failed', array_merge([
            'operation' => $operation,
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ], $context));
    }
}


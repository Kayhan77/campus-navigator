<?php

namespace App\Services\Event;

use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\UpdateEventDTO;
use App\Filters\EventFilter;
use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use App\Services\Cache\CacheTags;
use App\Services\FirebaseService;
use App\Services\Search\SearchCacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EventService
{
    private const IMAGE_DISK = 'public';
    private const IMAGE_PATH = 'events';

    public function __construct(
        private readonly SearchCacheService $cache,
        private readonly FirebaseService $firebase
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
            fn () => Event::filter($filter)->withAllowed($request, [])->paginate($perPage)
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
     * Create event with image upload
     */
    public function create(CreateEventDTO $dto, int $userId, ?UploadedFile $image = null): Event
    {
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

        return $event;
    }

    /**
     * Update event with optional image replacement
     */
    public function update(Event $event, UpdateEventDTO $dto, ?UploadedFile $image = null): Event
    {
        $this->guardTerminalStatus($event);

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
        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

        return Storage::disk(self::IMAGE_DISK)
            ->putFileAs(self::IMAGE_PATH, $image, $filename);
    }

    /**
     * Delete image from storage safely
     */
    private function deleteImage(string $imagePath): void
    {
        if (Storage::disk(self::IMAGE_DISK)->exists($imagePath)) {
            Storage::disk(self::IMAGE_DISK)->delete($imagePath);
        }
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
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->each(function (string $token) use ($title, $body, $data): void {
                $this->firebase->sendNotification($token, $title, $body, $data);
            });
    }
}


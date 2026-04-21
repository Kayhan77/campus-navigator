<?php

namespace App\Services;

use App\DTOs\LostItem\CreateLostItemDTO;
use App\DTOs\LostItem\UpdateLostItemDTO;
use App\Models\ItemClaim;
use App\Filters\LostFoundFilter;
use App\Models\LostItem;
use App\Services\Cache\CacheTags;
use App\Services\Notification\NotificationService;
use App\Services\Search\SearchCacheService;
use App\Services\SupabaseStorageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LostItemService
{
    public function __construct(
        private readonly SearchCacheService $cache,
        private readonly NotificationService $notificationService,
        private readonly SupabaseStorageService $supabaseStorage
    ) {}

    // -------------------------------------------------------------------------
    // Listings
    // -------------------------------------------------------------------------

    /**
     * Filtered, cached, paginated list.
     *
     * $scopedUserId — when non-null the query is restricted to that user's items.
     * Pass null for admins (see all) or unauthenticated contexts.
     * The caller decides whether scoping applies; this service only enforces it.
     *
     * Cache is tagged with CacheTags::LOST_ITEMS; LostItemObserver flushes it
     * automatically on any create / update / delete.
     */
    public function listPaginated(
        LostFoundFilter $filter,
        Request $request,
        int $perPage,
        ?int $scopedUserId = null
    ): LengthAwarePaginator {
        $key = SearchCacheService::buildKey(
            CacheTags::LOST_ITEMS,
            $filter,
            (int) $request->input('page', 1),
            $perPage,
            $scopedUserId,
        );

        return $this->cache->remember(
            CacheTags::LOST_ITEMS,
            $key,
            function () use ($filter, $request, $perPage, $scopedUserId) {
                $query = LostItem::query();

                if ($scopedUserId !== null) {
                    $query->where('user_id', $scopedUserId);
                }

                return $query->filter($filter)->withAllowed($request, ['user'])->paginate($perPage);
            }
        );
    }

    // -------------------------------------------------------------------------
    // Single record
    // -------------------------------------------------------------------------

    public function getById(LostItem $item): LostItem
    {
        return $item->load('user');
    }

    // -------------------------------------------------------------------------
    // Writes  (cache invalidation handled by LostItemObserver)
    // -------------------------------------------------------------------------

    public function create(CreateLostItemDTO $dto, int $userId): LostItem
    {
        $data = array_merge($dto->toArray(), [
            'user_id' => $userId,
        ]);

        if ($dto->image !== null) {
            try {
                $path = $this->supabaseStorage->uploadImage($dto->image, 'lost-found');
                $data['image'] = $path;
            } catch (\Exception $e) {
                Log::warning('[Lost&Found] Image upload failed during create', ['error' => $e->getMessage()]);
            }
        }

        return LostItem::create($data);
    }

    public function update(LostItem $item, UpdateLostItemDTO $dto): LostItem
    {
        $data = $dto->toArray();

        if ($dto->image !== null) {
            try {
                if ($item->image) {
                    $this->supabaseStorage->delete($item->image);
                }
                $path = $this->supabaseStorage->uploadImage($dto->image, 'lost-found');
                $data['image'] = $path;
            } catch (\Exception $e) {
                Log::warning('[Lost&Found] Image upload failed during update', ['error' => $e->getMessage()]);
                unset($data['image']);
            }
        }

        $wasFound = $item->status === 'found';
        $item->update($data);
        $updated = $item->fresh();

        if (! $wasFound && $updated->status === 'found') {
            $this->notifyClaimantsItemResolved($updated);
        }

        return $updated;
    }

    public function delete(LostItem $item): void
    {
        if ($item->image) {
            $this->supabaseStorage->delete($item->image);
        }
        $item->delete();
    }

    private function notifyClaimantsItemResolved(LostItem $item): void
    {
        $recipientIds = ItemClaim::query()
            ->where('lost_item_id', $item->id)
            ->get()
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($recipientIds)) {
            return;
        }

        $this->notificationService->sendAndStoreNotification(
            title: 'Lost Item Resolved',
            message: $item->title,
            type: 'system',
            data: ['context' => 'lost_found', 'lost_item_id' => (int) $item->id],
            userIds: $recipientIds,
            senderId: $item->user_id
        );
    }
}

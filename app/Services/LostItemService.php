<?php

namespace App\Services;

use App\DTOs\LostItem\CreateLostItemDTO;
use App\DTOs\LostItem\UpdateLostItemDTO;
use App\Filters\LostFoundFilter;
use App\Models\LostItem;
use App\Services\Cache\CacheTags;
use App\Services\Search\SearchCacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class LostItemService
{
    public function __construct(
        private readonly SearchCacheService $cache
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
        return LostItem::create(array_merge($dto->toArray(), [
            'user_id' => $userId,
        ]));
    }

    public function update(LostItem $item, UpdateLostItemDTO $dto): LostItem
    {
        $item->update($dto->toArray());
        return $item->fresh();
    }

    public function delete(LostItem $item): void
    {
        $item->delete();
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\LostItem\CreateLostItemDTO;
use App\DTOs\LostItem\UpdateLostItemDTO;
use App\Services\LostItemService;
use App\Http\Requests\LostItem\LostItemRequest;
use App\Http\Requests\LostItem\UpdateLostItemRequest;
use App\Http\Resources\Api\V1\LostItemResource;
use App\Models\LostItem;
use App\Http\Controllers\Controller;
use App\Filters\LostFoundFilter;
use App\Services\Search\SearchCacheService;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class LostFoundController extends Controller
{
    protected LostItemService $service;

    public function __construct(LostItemService $service)
    {
        $this->service = $service;
    }

    // List all lost items
    public function index(Request $request, LostFoundFilter $filter, SearchCacheService $cache)
    {
        $user     = $request->user();
        $perPage  = max(1, min((int) $request->input('per_page', config('search.default_per_page', 15)), config('search.max_per_page', 50)));
        $cacheKey = SearchCacheService::buildKey('lost_items', $filter, $request->input('page', 1), $perPage, $user?->id);

        $paginator = $cache->remember('lost_items', $cacheKey, function () use ($filter, $request, $user, $perPage) {
            $query = LostItem::query();

            if ($user && ! $user->isAdmin()) {
                $query->where('user_id', $user->id);
            }

            // 'user' is a safe default; LostItem::$allowedIncludes controls extras.
            return $query->filter($filter)->withAllowed($request, ['user'])->paginate($perPage);
        });

        $paginator->getCollection()->transform(fn ($item) => new LostItemResource($item));

        return ApiResponse::paginated($paginator, 'Lost items retrieved successfully.');
    }

    // Show single lost item
    public function show(LostItem $lostItem)
    {
        return new LostItemResource($this->service->getById($lostItem));
    }

    // Create new lost item
    public function store(LostItemRequest $request)
    {
        $this->authorize('create', LostItem::class);

        $dto = CreateLostItemDTO::fromRequest($request);
        $item = $this->service->create($dto, $request->user()->id);

        return new LostItemResource($item);
    }

    // Update lost item
    public function update(UpdateLostItemRequest $request, LostItem $lostItem)
    {
        $this->authorize('update', $lostItem);

        $dto = new UpdateLostItemDTO($request->validated());
        $item = $this->service->update($lostItem, $dto);

        return new LostItemResource($item);
    }

    // Delete lost item
    public function destroy(LostItem $lostItem)
    {
        $this->authorize('delete', $lostItem);

        $this->service->delete($lostItem);

        return ['message' => 'Item deleted successfully'];
    }
}

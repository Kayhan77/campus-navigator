<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\DTOs\LostItem\CreateLostItemDTO;
use App\DTOs\LostItem\UpdateLostItemDTO;
use App\Filters\LostFoundFilter;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LostItem\LostItemRequest;
use App\Http\Requests\LostItem\UpdateLostItemRequest;
use App\Http\Resources\Api\V1\LostItemResource;
use App\Models\LostItem;
use App\Services\LostItemService;
use Illuminate\Http\Request;

class LostFoundController extends Controller
{
    public function __construct(
        private readonly LostItemService $service
    ) {}

    public function index(Request $request, LostFoundFilter $filter)
    {
        $user = $request->user();

        // Non-admin authenticated users may only see their own items.
        // Null = no scope → admins and unauthenticated requests see everything.
        $scopedUserId = ($user && ! $user->hasAnyRole(UserRole::adminRoles())) ? $user->id : null;

        $paginator = $this->service->listPaginated(
            $filter,
            $request,
            $this->resolvePerPage($request),
            $scopedUserId,
        );

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator->getCollection()->transform(fn ($item) => new LostItemResource($item));

        return ApiResponse::paginated($paginator, 'Lost items retrieved successfully.');
    }

    public function show(LostItem $lostItem)
    {
        return ApiResponse::success(
            new LostItemResource($this->service->getById($lostItem)),
            'Lost item retrieved successfully.'
        );
    }

    public function store(LostItemRequest $request)
    {                                              
        $this->authorize('create', LostItem::class);

        $dto  = CreateLostItemDTO::fromRequest($request);
        $item = $this->service->create($dto, $request->user()->id);

        return ApiResponse::success(new LostItemResource($item), 'Lost item reported successfully.', 201);
    }

    public function update(UpdateLostItemRequest $request, LostItem $lostItem)
    {
        $this->authorize('update', $lostItem);

        $dto  = UpdateLostItemDTO::fromRequest($request);
        $item = $this->service->update($lostItem, $dto);

        return ApiResponse::success(new LostItemResource($item), 'Lost item updated successfully.');
    }

    public function destroy(LostItem $lostItem)
    {
        $this->authorize('delete', $lostItem);

        $this->service->delete($lostItem);

        return ApiResponse::success(null, 'Lost item deleted successfully.');
    }
}

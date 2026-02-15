<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\LostItem\CreateLostItemDTO;
use App\DTOs\LostItem\UpdateLostItemDTO;
use App\Services\LostItemService;
use App\Http\Requests\LostItem\LostItemRequest;
use App\Http\Requests\LostItem\UpdateLostItemRequest;
use App\Http\Resources\Api\V1\LostItemResource;
use App\Models\LostItem;
use Illuminate\Routing\Controller;

class LostFoundController extends Controller
{
    protected LostItemService $service;

    public function __construct(LostItemService $service)
    {
        $this->service = $service;
    }

    // List all lost items
    public function index()
    {
        return LostItemResource::collection($this->service->getAll());
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

<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\LostItem\CreateLostItemDTO;
use App\DTOs\LostItem\UpdateLostItemDTO;
use App\Services\LostItemService;
use App\Http\Requests\LostItem\LostItemRequest;
use App\Http\Requests\LostItem\UpdateLostItemRequest;
use App\Http\Resources\Api\V1\LostItemResource;
use App\Models\LostItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;


class LostFoundController extends Controller
{
    protected LostItemService $service;

    public function __construct(LostItemService $service)
    {
        $this->service = $service;
    }

    /**
     * List all lost items
     */
    public function index()
    {
        return $this->service->index();
    }

    /**
     * Store a new lost item
     */
    public function store(LostItemRequest $request)
    {
        $data = new CreateLostItemDTO($request->validated());
        $item = $this->service->create($data, $request->user()->id);
        return new LostItemResource($item);
    }

    /**
     * Show a single lost item
     */
    public function show(LostItem $lostItem)
    {
        return $this->service->show($lostItem);
    }

    /**
     * Update a lost item
     */
    public function update(UpdateLostItemRequest $request, LostItem $lostItem): JsonResponse
    {
        $this->authorize('update', $lostItem);

        $data = new UpdateLostItemDTO($request->validated());
        $item = $this->service->update($lostItem, $data);

        return response()->json(new LostItemResource($item));
    }

    /**
     * Delete a lost item
     */
    public function destroy(LostItem $lostItem)
    {
        $this->service->delete($lostItem);

        return response()->json([
            'status' => true,
            'message' => 'Item deleted successfully'
        ]);
    }
}

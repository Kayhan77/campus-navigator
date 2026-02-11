<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\LostItemData;
use App\Services\LostItemService;
use App\Http\Requests\LostItemRequest;
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
        $data = new LostItemData($request->validated());
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
    public function update(LostItemRequest $request, LostItem $lostItem)
    {
        $data = new LostItemData($request->validated());
        $item = $this->service->update($lostItem, $data);

        return new LostItemResource($item);
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

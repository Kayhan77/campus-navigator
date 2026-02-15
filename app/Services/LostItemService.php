<?php

namespace App\Services;

use App\DTOs\LostItem\CreateLostItemDTO;
use App\DTOs\LostItem\UpdateLostItemDTO;
use App\Models\LostItem;
use App\Http\Resources\Api\V1\LostItemResource;
use App\Exceptions\ApiException;


class LostItemService
{
    /**
     * List all lost items
     */
    public function index()
    {
        return LostItemResource::collection(LostItem::with('user')->get());
    }

    /**
     * Create a new lost item
     */
    public function create(CreateLostItemDTO $data, int $userId): LostItem
    {
        return LostItem::create([
            'title' => $data->title,
            'description' => $data->description,
            'location' => $data->location,
            'status' => $data->status,
            'user_id' => $userId,
        ]);
    }

    /**
     * Show a specific lost item
     */
    public function show(LostItem $item)
    {
        return new LostItemResource($item->load('user'));
    }

    /**
     * Update a lost item
     */
    public function update(LostItem $item, UpdateLostItemDTO $data): LostItem
    {
        $item->update((array) $data);
        return $item;
    }

    /**
     * Delete a lost item
     */
    public function delete(LostItem $item): bool
    {
        return $item->delete();
    }

     public function getAll()
    {
        return LostItem::with('user')->get();
    }

    public function getById(int $id): LostItem
    {
        return LostItem::with('user')->findOrFail($id);
    }
}

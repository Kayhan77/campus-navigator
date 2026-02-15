<?php

namespace App\Services;

use App\DTOs\LostItem\CreateLostItemDTO;
use App\DTOs\LostItem\UpdateLostItemDTO;
use App\Models\LostItem;

class LostItemService
{
    public function create(CreateLostItemDTO $data, int $userId): LostItem
    {
        return LostItem::create(array_merge($data->toArray(), [
            'user_id' => $userId
        ]));
    }

    public function update(LostItem $item, UpdateLostItemDTO $data): LostItem
    {
        $item->update($data->toArray());
        return $item;
    }

    public function delete(LostItem $item): bool
    {
        return $item->delete();
    }

    public function getAll()
    {
        return LostItem::with('user')->get();
    }

    public function getById(LostItem $item): LostItem
    {
        return $item->load('user');
    }
}

<?php

namespace App\Services;

use App\DTOs\LostItem\CreateLostItemDTO;
use App\DTOs\LostItem\UpdateLostItemDTO;
use App\Models\LostItem;
use Illuminate\Support\Facades\Cache;

class LostItemService
{
    public function create(CreateLostItemDTO $data, int $userId): LostItem
    {
        $lostItem = LostItem::create(array_merge($data->toArray(), [
            'user_id' => $userId
        ]));
        Cache::forget('lost_items');
        return $lostItem;
    }

    public function update(LostItem $item, UpdateLostItemDTO $data): LostItem
    {
        $item->update($data->toArray());
        Cache::forget('lost_items');
        return $item;
    }

    public function delete(LostItem $item): bool
    {
        $deleted = $item->delete();
        Cache::forget('lost_items');
        return $deleted;
    }

    public function getAll()
    {
        return cache::remember('lost_items', 60, function () {
            return LostItem::with('user')->get();
        });
    }

    public function getById(LostItem $item): LostItem
    {
        return $item->load('user');
    }
}

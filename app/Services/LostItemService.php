<?php

namespace App\Services;

use App\Models\LostItem;
use App\DTOs\LostItem\LostItemData;

class LostItemService
{
    public function create(LostItemData $data, int $userId): LostItem
    {
        return LostItem::create([
            'title' => $data->title,
            'description' => $data->description,
            'location' => $data->location,
            'status' => $data->status,
            'user_id' => $userId,
        ]);
    }
}

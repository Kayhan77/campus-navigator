<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemClaim extends Model
{
    protected $fillable = [
        'lost_item_id',
        'user_id',
        'message',
        'location_found',
        'status',
    ];

    public function lostItem(): BelongsTo
    {
        return $this->belongsTo(LostItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

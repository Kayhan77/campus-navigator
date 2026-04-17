<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LostItem extends Model
{
    use Filterable;

    /** Relations the client may request via ?include=user */
    protected array $allowedIncludes = ['user'];

    protected $fillable = ['title', 'description', 'location', 'status', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function itemClaims(): HasMany
    {
        return $this->hasMany(ItemClaim::class);
    }
}

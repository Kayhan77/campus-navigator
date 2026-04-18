<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Building extends Model
{
    use Filterable;

    /** Relations the client may request via ?include=rooms */
    protected array $allowedIncludes = ['rooms'];

    protected $fillable = ['name', 'type', 'category', 'latitude', 'longitude', 'description', 'image', 'opening_hours', 'notes'];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function events()
    {
        return $this->hasManyThrough(Event::class, Room::class);
    }
}

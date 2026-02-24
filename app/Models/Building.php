<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use Filterable;

    /** Relations the client may request via ?include=rooms */
    protected array $allowedIncludes = ['rooms'];

    protected $fillable = ['name', 'latitude', 'longitude', 'description'];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}

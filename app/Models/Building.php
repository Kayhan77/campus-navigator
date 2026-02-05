<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $fillable = ['name', 'latitude', 'longitude', 'description'];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}

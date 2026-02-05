<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LostItem extends Model
{
    protected $fillable = ['title', 'description', 'location', 'status', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

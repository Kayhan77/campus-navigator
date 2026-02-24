<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'location',
        'start_time',
        'end_time',
        'reminders_dispatched',
        'created_by',
    ];

    protected $casts = [
        'start_time'           => 'datetime',
        'end_time'             => 'datetime',
        'reminders_dispatched' => 'array',   // JSON array of dispatched window keys
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

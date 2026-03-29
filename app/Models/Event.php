<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Event extends Model
{
    use Filterable;

    /**
     * Relations the client may request via ?include=room,building.
     * Anything not listed here is silently ignored by scopeWithAllowed().
     */
    protected array $allowedIncludes = ['room', 'building'];

    protected $fillable = [
        'room_id',
        'title',
        'description',
        'location',
        'location_override',
        'start_time',
        'end_time',
        'status',
        'is_public',
        'max_attendees',
        'registration_required',
        'reminder_sent_at',
        'reminders_dispatched',
        'created_by',
    ];

    protected $casts = [
        'start_time'            => 'datetime',
        'end_time'              => 'datetime',
        'reminder_sent_at'      => 'datetime',
        'is_public'             => 'boolean',
        'registration_required' => 'boolean',
        'reminders_dispatched'  => 'array',   // JSON array of dispatched window keys
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function building()
    {
        return $this->hasOneThrough(Building::class, Room::class, 'id', 'id', 'room_id', 'building_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

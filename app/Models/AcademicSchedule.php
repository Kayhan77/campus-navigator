<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

class AcademicSchedule extends Model
{
    use Filterable;

    /** Relations the client may request via ?include=room or ?include=room.building */
    protected array $allowedIncludes = ['room', 'room.building'];

    protected $fillable = [
        'course_name',
        'day',
        'start_time',
        'end_time',
        'room_id'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}

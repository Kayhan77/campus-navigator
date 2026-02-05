<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSchedule extends Model
{
    protected $fillable = [
        'course_name',
        'day',
        'start_time',
        'end_time',
        'room_id'
    ];
}

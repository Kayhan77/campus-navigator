<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class PendingRegistration extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'token',
    ];

    protected $hidden = [
        'password',
        'token',
    ];

    protected $casts = [];

    public function routeNotificationForMail()
    {
        return $this->email;
    }

}

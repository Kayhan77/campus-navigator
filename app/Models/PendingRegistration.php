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
        'verification_code',
        'expires_at',
    ];

    protected $hidden = [
        'password',
        'token',
        'verification_code',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function routeNotificationForMail()
    {
        return $this->email;
    }

}

<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    protected $fillable = ['email', 'token', 'expires_at'];
    public $timestamps = true;
    protected $dates = ['expires_at'];
}

<?php namespace App\Models; 

use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject; 
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Foundation\Auth\User as Authenticatable; 
use Illuminate\Notifications\Notifiable; 

class User extends Authenticatable implements JWTSubject { 
    use HasFactory, Notifiable; 
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_verified',
        'email_verified_at',
    ]; 

    protected $hidden = [ 
        'password', 
        'remember_token', 
    ]; 

    protected $casts = [ 
        'email_verified_at' => 'datetime', 
        'password' => 'hashed', 
        'is_verified' => 'boolean', 
    ]; 
    
    public function lostItems() { 
        return $this->hasMany(LostItem::class); 
    } 
    public function events() { 
        return $this->hasMany(Event::class, 'created_by'); 
    } 
        
    public function getJWTIdentifier() { 
            return $this->getKey(); 
    } 
    
    public function getJWTCustomClaims(): array { 
        return []; 
    } 
    
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }
}
<?php namespace App\Models; 

use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject; 
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Foundation\Auth\User as Authenticatable; 
use Illuminate\Notifications\Notifiable;
use App\Models\DeviceToken; 

class User extends Authenticatable implements JWTSubject { 
    use HasFactory, Notifiable; 
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_verified',
        'email_verified_at',
        'notification_preferences',
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

    /**
     * Device tokens registered for push notifications.
     */
    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Push notification preferences stored as a JSON object.
     *
     * Returns an array with defaults merged in so callers never need
     * to null-check individual keys:
     *   'enabled'   => true
     *   'reminders' => ['24h', '1h', '10min']
     *   'locale'    => 'en'
     */
    public function getNotificationPreferencesAttribute(?string $value): array
    {
        $defaults = [
            'enabled'   => true,
            'reminders' => ['24h', '1h', '10min'],
            'locale'    => 'en',
        ];

        if ($value === null) {
            return $defaults;
        }

        $stored = json_decode($value, true) ?? [];

        return array_merge($defaults, $stored);
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
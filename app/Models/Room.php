<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
                                                
class Room extends Model
{
    use Filterable;

    /** Relations the client may request via ?include=building */
    protected array $allowedIncludes = ['building'];

    protected $fillable = ['building_id', 'room_number', 'floor'];

    public function building(): belongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(AcademicSchedule::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class); 
    }
}

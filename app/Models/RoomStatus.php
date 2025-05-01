<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'is_active',
        'is_available',
        'is_occupied',
        'is_maintenance',
        'is_cleaning',
        'priority'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'is_occupied' => 'boolean',
        'is_maintenance' => 'boolean',
        'is_cleaning' => 'boolean'
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class, 'status_code', 'code');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeOccupied($query)
    {
        return $query->where('is_occupied', true);
    }

    public function scopeMaintenance($query)
    {
        return $query->where('is_maintenance', true);
    }

    public function scopeCleaning($query)
    {
        return $query->where('is_cleaning', true);
    }

    public function scopeByPriority($query, $direction = 'asc')
    {
        return $query->orderBy('priority', $direction);
    }

    public function getIsAvailableAttribute()
    {
        return $this->is_available;
    }

    public function getIsOccupiedAttribute()
    {
        return $this->is_occupied;
    }

    public function getIsUnderMaintenanceAttribute()
    {
        return $this->is_maintenance;
    }

    public function getIsBeingCleanedAttribute()
    {
        return $this->is_cleaning;
    }
}
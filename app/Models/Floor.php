<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Floor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
        'description',
        'floor_plan',
        'is_active'
    ];

    protected $casts = [
        'floor_plan' => 'array',
        'is_active' => 'boolean'
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function getAvailableRoomsCountAttribute()
    {
        return $this->rooms()->available()->count();
    }

    public function getOccupiedRoomsCountAttribute()
    {
        return $this->rooms()->occupied()->count();
    }

    public function getMaintenanceRoomsCountAttribute()
    {
        return $this->rooms()->maintenance()->count();
    }

    public function getCleaningRoomsCountAttribute()
    {
        return $this->rooms()->cleaning()->count();
    }
}
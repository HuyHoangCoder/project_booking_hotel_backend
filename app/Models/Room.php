<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_number',
        'name',
        'description',
        'room_type_id',
        'floor_id',
        'capacity',
        'price_per_night',
        'amenities',
        'images',
        'status',
        'is_smoking_allowed',
        'has_balcony',
        'has_view',
        'size',
        'bed_count',
        'bed_type'
    ];

    protected $casts = [
        'amenities' => 'array',
        'images' => 'array',
        'price_per_night' => 'decimal:2',
        'is_smoking_allowed' => 'boolean',
        'has_balcony' => 'boolean',
        'has_view' => 'boolean'
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    public function scopeMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    public function scopeCleaning($query)
    {
        return $query->where('status', 'cleaning');
    }

    public function scopeByType($query, $typeId)
    {
        return $query->where('room_type_id', $typeId);
    }

    public function scopeByFloor($query, $floorId)
    {
        return $query->where('floor_id', $floorId);
    }

    public function scopeByCapacity($query, $capacity)
    {
        return $query->where('capacity', '>=', $capacity);
    }

    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price_per_night', [$minPrice, $maxPrice]);
    }

    public function scopeWithAmenities($query, array $amenities)
    {
        return $query->whereJsonContains('amenities', $amenities);
    }

    public function getIsAvailableAttribute()
    {
        return $this->status === 'available';
    }

    public function getIsOccupiedAttribute()
    {
        return $this->status === 'occupied';
    }

    public function getIsUnderMaintenanceAttribute()
    {
        return $this->status === 'maintenance';
    }

    public function getIsBeingCleanedAttribute()
    {
        return $this->status === 'cleaning';
    }
}
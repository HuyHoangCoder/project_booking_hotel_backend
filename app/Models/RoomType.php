<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'base_price',
        'max_capacity',
        'default_amenities',
        'images',
        'is_active'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'default_amenities' => 'array',
        'images' => 'array',
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

    public function scopeByCapacity($query, $capacity)
    {
        return $query->where('max_capacity', '>=', $capacity);
    }

    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('base_price', [$minPrice, $maxPrice]);
    }

    public function getHasAvailableRoomsAttribute()
    {
        return $this->rooms()->available()->exists();
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

    public function getTotalRoomsCountAttribute()
    {
        return $this->rooms()->count();
    }

    public function getAveragePriceAttribute()
    {
        return $this->rooms()->avg('price_per_night') ?? $this->base_price;
    }

    public function getPopularityAttribute()
    {
        $totalRooms = $this->total_rooms_count;
        if ($totalRooms === 0) {
            return 0;
        }

        $occupiedRooms = $this->occupied_rooms_count;
        return ($occupiedRooms / $totalRooms) * 100;
    }

    public function getRevenueAttribute()
    {
        return $this->rooms()
            ->whereHas('bookings', function ($query) {
                $query->where('status', 'confirmed')
                    ->orWhere('status', 'checked_in');
            })
            ->sum('price_per_night');
    }
}
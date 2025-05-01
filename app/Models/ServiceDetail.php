<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'unit',
        'duration',
        'is_available',
        'requires_booking',
        'min_booking_hours',
        'max_booking_hours',
        'operating_hours',
        'requirements',
        'included_items',
        'additional_charges',
        'images'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'decimal:2',
        'is_available' => 'boolean',
        'requires_booking' => 'boolean',
        'min_booking_hours' => 'integer',
        'max_booking_hours' => 'integer',
        'operating_hours' => 'array',
        'requirements' => 'array',
        'included_items' => 'array',
        'additional_charges' => 'array',
        'images' => 'array'
    ];

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeRequiresBooking($query)
    {
        return $query->where('requires_booking', true);
    }

    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    public function scopeByDuration($query, $duration)
    {
        return $query->where('duration', $duration);
    }

    public function scopeByUnit($query, $unit)
    {
        return $query->where('unit', $unit);
    }

    public function calculateTotalPrice($quantity = 1, $hours = null)
    {
        $total = $this->price * $quantity;

        if ($this->requires_booking && $hours) {
            if ($this->min_booking_hours && $hours < $this->min_booking_hours) {
                $hours = $this->min_booking_hours;
            }
            if ($this->max_booking_hours && $hours > $this->max_booking_hours) {
                $hours = $this->max_booking_hours;
            }
            $total *= $hours;
        }

        // Add additional charges if any
        if (!empty($this->additional_charges)) {
            foreach ($this->additional_charges as $charge) {
                if (isset($charge['type']) && isset($charge['amount'])) {
                    if ($charge['type'] === 'fixed') {
                        $total += $charge['amount'];
                    } elseif ($charge['type'] === 'percentage') {
                        $total += ($total * $charge['amount'] / 100);
                    }
                }
            }
        }

        return $total;
    }

    public function isOperatingNow()
    {
        if (empty($this->operating_hours)) {
            return true;
        }

        $now = now();
        $dayOfWeek = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        if (!isset($this->operating_hours[$dayOfWeek])) {
            return false;
        }

        $hours = $this->operating_hours[$dayOfWeek];
        if (empty($hours)) {
            return false;
        }

        foreach ($hours as $timeSlot) {
            if (isset($timeSlot['start']) && isset($timeSlot['end'])) {
                if ($currentTime >= $timeSlot['start'] && $currentTime <= $timeSlot['end']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getAvailableTimeSlots($date)
    {
        if (!$this->requires_booking) {
            return [];
        }

        $dayOfWeek = strtolower($date->format('l'));
        if (empty($this->operating_hours[$dayOfWeek])) {
            return [];
        }

        $timeSlots = [];
        foreach ($this->operating_hours[$dayOfWeek] as $slot) {
            if (isset($slot['start']) && isset($slot['end'])) {
                $start = \Carbon\Carbon::parse($slot['start']);
                $end = \Carbon\Carbon::parse($slot['end']);

                while ($start->addHours($this->duration)->lte($end)) {
                    $timeSlots[] = [
                        'start' => $start->format('H:i'),
                        'end' => $start->addHours($this->duration)->format('H:i')
                    ];
                }
            }
        }

        return $timeSlots;
    }

    public function validateBooking($hours)
    {
        if (!$this->requires_booking) {
            return true;
        }

        if ($this->min_booking_hours && $hours < $this->min_booking_hours) {
            return false;
        }

        if ($this->max_booking_hours && $hours > $this->max_booking_hours) {
            return false;
        }

        return true;
    }
}
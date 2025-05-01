<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_number',
        'customer_id',
        'room_id',
        'check_in_date',
        'check_out_date',
        'number_of_guests',
        'total_amount',
        'discount_amount',
        'final_amount',
        'status',
        'special_requests',
        'additional_services',
        'confirmed_at',
        'checked_in_at',
        'checked_out_at',
        'cancelled_at',
        'cancellation_reason'
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'additional_services' => 'array',
        'confirmed_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['confirmed', 'checked_in']);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('check_in_date', '>=', Carbon::today())
                    ->where('status', 'confirmed');
    }

    public function scopeCurrent($query)
    {
        return $query->where('status', 'checked_in');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'checked_out');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function getDurationAttribute()
    {
        return Carbon::parse($this->check_in_date)->diffInDays($this->check_out_date);
    }

    public function getIsActiveAttribute()
    {
        return in_array($this->status, ['confirmed', 'checked_in']);
    }

    public function getIsUpcomingAttribute()
    {
        return $this->status === 'confirmed' && $this->check_in_date >= Carbon::today();
    }

    public function getIsCurrentAttribute()
    {
        return $this->status === 'checked_in';
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'checked_out';
    }

    public function getIsCancelledAttribute()
    {
        return $this->status === 'cancelled';
    }
}
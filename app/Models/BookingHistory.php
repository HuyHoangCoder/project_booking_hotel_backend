<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'employee_id',
        'action',
        'changes',
        'notes'
    ];

    protected $casts = [
        'changes' => 'array'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
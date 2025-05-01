<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'citizen_identity',
        'fullname',
        'gender',
        'dob',
        'email',
        'avatar',
        'created_date',
        'is_active',
        'role_id'
    ];

    protected $casts = [
        'dob' => 'date',
        'created_date' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function account()
    {
        return $this->hasOne(Account::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function lostItems()
    {
        return $this->hasMany(LostItem::class);
    }
}
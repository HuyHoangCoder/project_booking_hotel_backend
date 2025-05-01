<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'fullname',
        'citizen_identity',
        'phone',
        'email',
        'dob',
        'customer_type'
    ];

    protected $casts = [
        'dob' => 'date'
    ];

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type', 'id');
    }

    public function groups()
    {
        return $this->belongsToMany(CustomerGroup::class, 'group_members', 'customer_id', 'group_id')
                    ->withPivot(['fullname', 'relationship']);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function lostItems()
    {
        return $this->hasMany(LostItem::class);
    }
}
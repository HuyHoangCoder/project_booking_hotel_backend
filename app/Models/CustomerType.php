<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'discount_percentage',
        'minimum_points',
        'is_active',
        'benefits'
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'minimum_points' => 'integer',
        'is_active' => 'boolean',
        'benefits' => 'array'
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'customer_type', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithMinimumPoints($query, $points)
    {
        return $query->where('minimum_points', '<=', $points);
    }
} 
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'discount_percentage',
        'is_active',
        'benefits'
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'benefits' => 'array'
    ];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'group_members', 'group_id', 'customer_id')
                    ->withPivot(['fullname', 'relationship'])
                    ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getActiveMembersCountAttribute()
    {
        return $this->customers()->count();
    }
}
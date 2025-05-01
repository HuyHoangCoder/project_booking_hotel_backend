<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_count',
        'per_user_limit',
        'start_date',
        'end_date',
        'is_active',
        'applicable_room_types',
        'applicable_customer_types',
        'applicable_customer_groups'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'per_user_limit' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'applicable_room_types' => 'array',
        'applicable_customer_types' => 'array',
        'applicable_customer_groups' => 'array'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function scopeAvailable($query)
    {
        return $query->where(function($q) {
            $q->whereNull('usage_limit')
              ->orWhereColumn('usage_count', '<', 'usage_limit');
        });
    }

    public function isActive()
    {
        return $this->is_active && 
               Carbon::parse($this->start_date)->lte(now()) && 
               Carbon::parse($this->end_date)->gte(now());
    }

    public function isAvailable()
    {
        return $this->isActive() && 
               (!$this->usage_limit || $this->usage_count < $this->usage_limit);
    }

    public function calculateDiscount($amount)
    {
        if (!$this->isAvailable()) {
            return 0;
        }

        if ($this->min_order_amount && $amount < $this->min_order_amount) {
            return 0;
        }

        $discount = $this->type === 'percentage' 
            ? $amount * ($this->value / 100)
            : $this->value;

        if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
            return $this->max_discount_amount;
        }

        return $discount;
    }

    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    public function isApplicableToRoomType($roomTypeId)
    {
        if (empty($this->applicable_room_types)) {
            return true;
        }

        return in_array($roomTypeId, $this->applicable_room_types);
    }

    public function isApplicableToCustomerType($customerTypeId)
    {
        if (empty($this->applicable_customer_types)) {
            return true;
        }

        return in_array($customerTypeId, $this->applicable_customer_types);
    }

    public function isApplicableToCustomerGroup($customerGroupId)
    {
        if (empty($this->applicable_customer_groups)) {
            return true;
        }

        return in_array($customerGroupId, $this->applicable_customer_groups);
    }
}
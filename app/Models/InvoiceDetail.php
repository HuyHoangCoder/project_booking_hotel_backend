<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'item_type',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'tax_rate',
        'tax_amount',
        'discount_rate',
        'discount_amount',
        'details',
        'is_active'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'details' => 'array',
        'is_active' => 'boolean'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function calculateTotals()
    {
        // Calculate total price before tax and discount
        $this->total_price = $this->quantity * $this->unit_price;

        // Calculate tax amount
        $this->tax_amount = $this->total_price * ($this->tax_rate / 100);

        // Calculate discount amount
        $this->discount_amount = $this->total_price * ($this->discount_rate / 100);

        // Calculate final total price
        $this->total_price = $this->total_price + $this->tax_amount - $this->discount_amount;

        $this->save();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('item_type', $type);
    }

    public function scopeRoomItems($query)
    {
        return $query->where('item_type', 'room');
    }

    public function scopeServiceItems($query)
    {
        return $query->where('item_type', 'service');
    }

    public function scopeAmenityItems($query)
    {
        return $query->where('item_type', 'amenity');
    }

    public function scopeOtherItems($query)
    {
        return $query->where('item_type', 'other');
    }
} 
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryDeliveryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'item_name',
        'item_code',
        'unit',
        'quantity',
        'unit_price',
        'total_price',
        'batch_number',
        'expiry_date',
        'storage_location',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'expiry_date' => 'date'
    ];

    public function delivery()
    {
        return $this->belongsTo(InventoryDelivery::class, 'delivery_id');
    }

    public function scopeByItemCode($query, $itemCode)
    {
        return $query->where('item_code', $itemCode);
    }

    public function scopeByBatchNumber($query, $batchNumber)
    {
        return $query->where('batch_number', $batchNumber);
    }

    public function scopeByStorageLocation($query, $location)
    {
        return $query->where('storage_location', $location);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', now()->addDays($days));
    }

    public function calculateTotalPrice()
    {
        return $this->quantity * $this->unit_price;
    }

    public function updateQuantity($newQuantity)
    {
        $this->quantity = $newQuantity;
        $this->total_price = $this->calculateTotalPrice();
        $this->save();
    }

    public function updateUnitPrice($newPrice)
    {
        $this->unit_price = $newPrice;
        $this->total_price = $this->calculateTotalPrice();
        $this->save();
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InventoryReceiving extends Model
{
    use HasFactory;

    protected $fillable = [
        'receiving_number',
        'receiving_date',
        'supplier_name',
        'supplier_contact',
        'supplier_address',
        'delivery_method',
        'tracking_number',
        'status',
        'notes',
        'attachments'
    ];

    protected $casts = [
        'receiving_date' => 'date',
        'attachments' => 'array'
    ];

    public function items()
    {
        return $this->hasMany(InventoryReceivingItem::class, 'receiving_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('receiving_date', [$startDate, $endDate]);
    }

    public function scopeBySupplier($query, $supplierName)
    {
        return $query->where('supplier_name', 'like', "%{$supplierName}%");
    }

    public function getTotalAmount()
    {
        return $this->items()->sum('total_price');
    }

    public function getItemCount()
    {
        return $this->items()->count();
    }

    public function markAsReceived()
    {
        if ($this->status === 'pending') {
            $this->update(['status' => 'received']);
            return true;
        }
        return false;
    }

    public function cancel()
    {
        if ($this->status === 'pending') {
            $this->update(['status' => 'cancelled']);
            return true;
        }
        return false;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->receiving_number)) {
                $model->receiving_number = 'REC-' . strtoupper(Str::random(8));
            }
        });
    }
}
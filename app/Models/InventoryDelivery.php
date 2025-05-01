<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InventoryDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_number',
        'delivery_date',
        'recipient_name',
        'recipient_contact',
        'recipient_address',
        'delivery_method',
        'tracking_number',
        'status',
        'notes',
        'attachments'
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'attachments' => 'array'
    ];

    public function items()
    {
        return $this->hasMany(InventoryDeliveryItem::class, 'delivery_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('delivery_date', [$startDate, $endDate]);
    }

    public function scopeByRecipient($query, $recipientName)
    {
        return $query->where('recipient_name', 'like', "%{$recipientName}%");
    }

    public function getTotalAmount()
    {
        return $this->items()->sum('total_price');
    }

    public function getItemCount()
    {
        return $this->items()->count();
    }

    public function markAsDelivered()
    {
        if ($this->status === 'pending') {
            $this->update(['status' => 'delivered']);
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
            if (empty($model->delivery_number)) {
                $model->delivery_number = 'DEL-' . strtoupper(Str::random(8));
            }
        });
    }
}
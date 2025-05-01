<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'invoice_id',
        'customer_id',
        'amount',
        'payment_method',
        'payment_status',
        'transaction_id',
        'payment_details',
        'payment_date',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'payment_date' => 'date'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('payment_status', 'refunded');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    public function scopeByAmountRange($query, $minAmount, $maxAmount)
    {
        return $query->whereBetween('amount', [$minAmount, $maxAmount]);
    }

    public function markAsCompleted()
    {
        $this->update(['payment_status' => 'completed']);
        $this->invoice->calculateTotals();
    }

    public function markAsFailed()
    {
        $this->update(['payment_status' => 'failed']);
    }

    public function markAsRefunded()
    {
        $this->update(['payment_status' => 'refunded']);
        $this->invoice->calculateTotals();
    }

    public function isRefundable()
    {
        return $this->payment_status === 'completed' && 
               $this->payment_date->diffInDays(now()) <= 30; // Only refundable within 30 days
    }
}
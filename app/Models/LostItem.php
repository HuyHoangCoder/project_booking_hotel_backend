<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LostItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_number',
        'item_name',
        'description',
        'category',
        'location_found',
        'date_found',
        'status',
        'storage_location',
        'finder_name',
        'finder_contact',
        'claimer_name',
        'claimer_contact',
        'claim_date',
        'claim_notes',
        'images',
        'notes'
    ];

    protected $casts = [
        'date_found' => 'date',
        'claim_date' => 'date',
        'images' => 'array'
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeClaimed($query)
    {
        return $query->where('status', 'claimed');
    }

    public function scopeDisposed($query)
    {
        return $query->where('status', 'disposed');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_found', [$startDate, $endDate]);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location_found', 'like', "%{$location}%");
    }

    public function scopeByStorageLocation($query, $location)
    {
        return $query->where('storage_location', 'like', "%{$location}%");
    }

    public function markAsClaimed($claimerName, $claimerContact, $claimNotes = null)
    {
        if ($this->status === 'pending') {
            $this->update([
                'status' => 'claimed',
                'claimer_name' => $claimerName,
                'claimer_contact' => $claimerContact,
                'claim_date' => now(),
                'claim_notes' => $claimNotes
            ]);
            return true;
        }
        return false;
    }

    public function markAsDisposed()
    {
        if ($this->status === 'pending') {
            $this->update(['status' => 'disposed']);
            return true;
        }
        return false;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->item_number)) {
                $model->item_number = 'LOST-' . strtoupper(Str::random(8));
            }
        });
    }
} 
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'icon',
        'is_active',
        'display_order',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'metadata' => 'array'
    ];

    public function serviceDetails()
    {
        return $this->hasMany(ServiceDetail::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function getActiveServiceDetails()
    {
        return $this->serviceDetails()->where('is_available', true)->get();
    }

    public function getServiceCount()
    {
        return $this->serviceDetails()->count();
    }

    public function getActiveServiceCount()
    {
        return $this->serviceDetails()->where('is_available', true)->count();
    }

    public function toggleStatus()
    {
        $this->update(['is_active' => !$this->is_active]);
        return $this;
    }

    public function updateDisplayOrder($order)
    {
        $this->update(['display_order' => $order]);
        return $this;
    }

    public function getMetadata($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->metadata;
        }

        return data_get($this->metadata, $key, $default);
    }

    public function setMetadata($key, $value)
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->update(['metadata' => $metadata]);
        return $this;
    }
}
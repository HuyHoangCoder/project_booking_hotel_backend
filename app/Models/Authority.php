<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Authority extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'permission',
        'details'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'username', 'username');
    }
} 
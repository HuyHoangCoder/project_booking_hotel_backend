<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'role_name',
        'description',
        'is_active',
    ];

    // Nếu bạn không dùng timestamps, thì set false
    public $timestamps = true;

    /**
     * Quan hệ 1-n với nhân viên (employees)
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'role_id');
    }
}

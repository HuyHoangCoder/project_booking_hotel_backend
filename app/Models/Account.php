<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use PharIo\Manifest\Author;

class Account extends Model
{
    use HasFactory, Notifiable;
    //dùng để định nghĩa các trường 
    protected $fillable = [
        'username',
        'password',
        'created_date',
        'is_active',
        'employee_id'
    ];
    //dùng để ẩn các trường khi model được chuyển thành mảng hoặc json 
    protected $hidden = [
        'password',
    ];
    //dùng để tự động chuyển kiểu dữ liệu khi truy xuất từ datase
    protected $casts = [
        'created_date' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];
    //mỗi bản ghi của model hiện tại (ví dụ: user) sẽ thuộc về một bản ghi trong bảng Employee
    public function employee(){
        return $this->belongsTo(Employee::class);
    }
    //một bản ghi của model hiện tại có thể có nhiều bản ghi trong authorities
    public function authorities(){
        return $this->hasMany(Authority::class, 'username', 'username');
    }
}

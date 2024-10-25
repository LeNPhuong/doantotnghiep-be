<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    // Đặt tên bảng nếu khác với mặc định
    protected $table = 'transactions';

    // Các thuộc tính có thể gán
    protected $fillable = [
        'user_id',
        'order_id',
        'total_price',
        'note',
        'name',
        'phone',
        'email',
        'payment_method',
    ];

    // Mối quan hệ với User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Mối quan hệ với Order
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}

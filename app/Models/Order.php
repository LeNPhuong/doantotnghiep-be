<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'code','user_id', 'status_id', 'voucher_id', 'total_price','cancellation_reason'
    ];
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    // Mối quan hệ với User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Mối quan hệ với Voucher
    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }
}

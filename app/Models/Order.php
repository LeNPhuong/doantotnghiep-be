<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Order extends Model
{
    use HasFactory, SoftDeletes, Searchable;

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

    public function transaction()
    {
        return $this->hasMany(Transaction::class, 'order_id');
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

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->user ? $this->user->name : null, // Thêm trường 'user_name' vào để tìm kiếm
            'status' => $this->status ? $this->status->text_status : null, // Thêm trường 'status' vào để tìm kiếm
        ];  
    }
}

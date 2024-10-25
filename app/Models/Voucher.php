<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $table = 'vouchers';

    protected $fillable = [
        'code', 'status', 'status', 'discount_type', 'discount_value', 'max_discount_value', 'description', 'quantity', 'used', 'start_date', 'end_date'
    ];
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_voucher')->withTimestamps();
    }
}

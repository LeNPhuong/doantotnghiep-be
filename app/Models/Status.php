<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Status extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'status';
    public $timestamps = false;
    protected $fillable = [
        'text_status',
        'active'
    ];

    // Định nghĩa mối quan hệ với bảng Order
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

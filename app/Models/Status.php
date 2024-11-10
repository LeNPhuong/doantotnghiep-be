<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Status extends Model
{
    use HasFactory;
    protected $table = 'status';
    public $timestamps = false;
    protected $fillable = [
        'text_status',
        'active'
    ];

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'text_status' => $this->text_status
        ];
    }

    // Định nghĩa mối quan hệ với bảng Order
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

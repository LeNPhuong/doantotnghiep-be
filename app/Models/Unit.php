<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Unit extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'units';

    protected $fillable = [
        'category_id', 'unit_id'
    ];

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name
        ];
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_unit', 'unit_id', 'category_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::retrieved(function ($unit) {
            // Ẩn trường pivot khi lấy dữ liệu
            $unit->makeHidden(['pivot','created_at', 'updated_at']);
        });
    }
}

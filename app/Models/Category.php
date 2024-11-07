<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;



class Category extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'key',
        'active'
    ];

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name
        ];
    }
    public function products()
    {
        return $this->hasMany(Product::class, 'cate_id');
    }

    public function units()
    {
        return $this->belongsToMany(category_unit::class, 'category_unit', 'category_id', 'unit_id');
    }

    public function activeUnits()
    {
        return $this->belongsToMany(Unit::class, 'category_unit')
                    ->where('units.active', 1)
                    ->withPivot('category_id', 'unit_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::retrieved(function ($unit) {
            // Ẩn trường pivot khi lấy dữ liệu
            $unit->makeHidden(['active', 'created_at', 'updated_at']);
        });
    }
}

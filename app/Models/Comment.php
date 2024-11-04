<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Comment extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = ['product_id', 'user_id', 'rating', 'comment', 'likes'];

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'comment' => $this->name
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

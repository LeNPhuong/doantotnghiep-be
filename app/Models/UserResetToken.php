<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserResetToken extends Model
{
    use HasFactory;

    protected $table = 'password_reset_tokens';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null; // Không cần `updated_at`
    protected $fillable = [
        'email', 'token',
    ];
}

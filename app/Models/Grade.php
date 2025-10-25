<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'cover', 'description', 'number', 'qrcode', 'poster', 'user_id', 'graduation_at'];
}

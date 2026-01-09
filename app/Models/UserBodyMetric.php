<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserBodyMetric extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'height', 'weight', 'age', 'body_fat_pct'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
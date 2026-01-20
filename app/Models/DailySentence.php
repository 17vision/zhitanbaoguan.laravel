<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailySentence extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'date', 'title', 'text', 'author', 'image'];
}

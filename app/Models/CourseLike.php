<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseLike extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'user_id'];
}

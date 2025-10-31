<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseMessagePraise extends Model
{
    use HasFactory;

    protected $fillable = ['course_message_id', 'user_id'];
}

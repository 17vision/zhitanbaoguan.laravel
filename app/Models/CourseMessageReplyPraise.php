<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseMessageReplyPraise extends Model
{
    use HasFactory;

    protected $fillable = ['course_message_reply_id', 'user_id', 'course_message_id'];
}

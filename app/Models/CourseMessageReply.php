<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class CourseMessageReply extends Model
{
    use HasFactory;

    protected $fillable = ['course_message_id', 'user_id', 'content', 'type', 'course_message_reply_id', 'praises_nums'];

    protected $appends = ['time'];

    public function getTimeAttribute()
    {
        $created_at = $this->attributes['created_at'];
        if ($created_at) {
            return Carbon::parse($created_at)->diffForHumans();
        }
        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function courseMessage() {
        return $this->belongsTo(CourseMessage::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class CourseMessage extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'user_id', 'content', 'type', 'praises_nums', 'reply_nums'];

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

    public function replies()
    {
        return $this->hasMany(CourseMessageReply::class, 'course_message_id', 'id');
    }
}

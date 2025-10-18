<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserCourseHomework extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'course_id', 'course_homework_id', 'content', 'score', 'evaluation'];

    public function getContentAttribute()
    {
        $content = $this->attributes['content'] ?? '';
        if ($content) {
            return json_decode($content, true);
        }
        return false;
    }
}
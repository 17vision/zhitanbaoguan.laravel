<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseStatistics extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'course_chapter_id', 'user_id', 'duration', 'position'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function course_chapter() 
    {
        return $this->belongsTo(CourseChapter::class);
    }
}

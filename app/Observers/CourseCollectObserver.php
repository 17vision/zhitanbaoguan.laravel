<?php

namespace App\Observers;

use App\Models\CourseCollect;
use App\Models\Course;

class CourseCollectObserver
{
    /**
     * Handle the CourseCollect "created" event.
     */
    public function created(CourseCollect $courseCollect): void
    {
        Course::query()->where('id', $courseCollect->course_id)->increment('collect_count');
    }

    /**
     * Handle the CourseCollect "deleted" event.
     */
    public function deleted(CourseCollect $courseCollect): void
    {
        Course::query()->where('id', $courseCollect->course_id)->decrement('collect_count');
    }
}

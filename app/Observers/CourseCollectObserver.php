<?php

namespace App\Observers;

use App\Models\CourseCollect;
use App\Models\Course;
use App\Models\UserExtend;

class CourseCollectObserver
{
    /**
     * Handle the CourseCollect "created" event.
     */
    public function created(CourseCollect $courseCollect): void
    {
        Course::query()->where('id', $courseCollect->course_id)->increment('collect_count');

        UserExtend::query()->where('user_id', $courseCollect->user_id)->increment('course_collect_count');
    }

    /**
     * Handle the CourseCollect "deleted" event.
     */
    public function deleted(CourseCollect $courseCollect): void
    {
        Course::query()->where('id', $courseCollect->course_id)->decrement('collect_count');

        UserExtend::query()->where('user_id', $courseCollect->user_id)->where('course_collect_count', '>', 0)->decrement('course_collect_count');
    }
}

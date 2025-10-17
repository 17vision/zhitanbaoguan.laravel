<?php

namespace App\Observers;

use App\Models\CourseLike;
use App\Models\Course;

class CourseLikeObserver
{
    /**
     * Handle the CourseLike "created" event.
     */
    public function created(CourseLike $courseLike): void
    {
        Course::query()->where('id', $courseLike->course_id)->increment('like_count');
    }


    /**
     * Handle the CourseLike "deleted" event.
     */
    public function deleted(CourseLike $courseLike): void
    {
        Course::query()->where('id', $courseLike->course_id)->decrement('like_count');
    }
}

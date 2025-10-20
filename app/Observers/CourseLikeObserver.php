<?php

namespace App\Observers;

use App\Models\CourseLike;
use App\Models\Course;
use App\Models\UserExtend;


class CourseLikeObserver
{
    /**
     * Handle the CourseLike "created" event.
     */
    public function created(CourseLike $courseLike): void
    {
        Course::query()->where('id', $courseLike->course_id)->increment('like_count');

        UserExtend::query()->where('user_id', $courseLike->user_id)->increment('course_like_count');
    }


    /**
     * Handle the CourseLike "deleted" event.
     */
    public function deleted(CourseLike $courseLike): void
    {
        Course::query()->where('id', $courseLike->course_id)->decrement('like_count');

        UserExtend::query()->where('user_id', $courseLike->user_id)->where('course_like_count', '>', 0)->decrement('course_like_count');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\CourseStatistics;
use App\Models\UserHomework;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class CourseStatisticsSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::query()->with(['chapters'])->where('status', 1)->get();

        $user_ids = UserHomework::query()->pluck('user_id')->all();

        $user_ids = array_unique($user_ids);

        foreach ($courses as $course) {
            $num = random_int(1, 20);
            $end = Carbon::now();

            for($i = $num; $i >0; $i--) {
                $uids = [... $user_ids];
                $uids = fake()->randomElements($uids, round((\count($uids)/2) + 1));
                $chapterIds = Arr::pluck($course->chapters, 'id');
                if (\count($chapterIds) > 0) {
                    $chapterIds = fake()->randomElements($chapterIds, round((\count($chapterIds)/2) + 1));

                    foreach($chapterIds as $chapterId) {
                        foreach($uids as $uid) {
                            CourseStatistics::create([
                                'course_id' => $course['id'], 
                                'date' => $end->copy()->toDateString(), 
                                'course_chapter_id' => $chapterId, 
                                'user_id' => $uid, 
                                'duration' => random_int(1, 1000), 
                                'position' => random_int(1, 50)
                            ]);
                        }
                    }
                }
                $end->addDays(-1);
            }
        }
    }
}

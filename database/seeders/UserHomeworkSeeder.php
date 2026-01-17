<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\CourseStatistics;
use App\Models\UserHomework;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class UserHomeworkSeeder extends Seeder
{
    public function run(): void
    {
        $ids = UserHomework::query()->whereNull('completed_at')->pluck('id')->all();
        
        $overtime = 27;

        $notfinish = 3;

        $ontime = count($ids) - $overtime - $notfinish;

        $ontime_ids = array_splice($ids, 0, $ontime);
  
        $overtime_ids = array_splice($ids, 0, $overtime);
        
        foreach($ontime_ids as $id) {
            $userHomework = UserHomework::query()->where('id', $id)->first();

            $minutes = Carbon::parse($userHomework['end_at'])->diffInMinutes(Carbon::parse($userHomework['created_at']));

            $diff_minutes = random_int(0, $minutes);

            $completed_at = Carbon::parse($userHomework['end_at'])->addMinutes(-1 * $diff_minutes)->toDateTimeString();

            $userHomework->update([
                'completed_at' => $completed_at,
                'status' => 1
            ]);
        }

        foreach($overtime_ids as $id) {
            $userHomework = UserHomework::query()->where('id', $id)->first();

            $hours = Carbon::now()->diffInHours(Carbon::parse($userHomework['end_at']));

            $diff_hours = random_int(0, $hours);

            $completed_at = Carbon::parse($userHomework['end_at'])->addHours($diff_hours)->toDateTimeString();

            $userHomework->update([
                'completed_at' => $completed_at,
                'status' => 2
            ]);
        }
    }
}

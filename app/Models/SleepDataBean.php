<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SleepDataBean extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'date', 'deep_sleep_count', 'light_sleep_count', 'rapid_eye_movement_count', 'start_at', 'end_at', 'total', 'deep_sleep_total', 'light_sleep_total', 'rapid_eye_movement_total', 'wake_count', 'wake_duration'];

    public function sleepData()
    {
        return $this->hasMany(SleepData::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SleepData extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'sleep_data_bean_id', 'type', 'start_at', 'end_at'];
}

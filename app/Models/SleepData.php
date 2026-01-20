<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SleepData extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'sleep_data_bean_id', 'type', 'start_at', 'end_at'];

    protected $appends = ['type_str'];

    public function getTypeStrAttribute()
    {
        $type = $this->getAttribute('type');

        $arr = ['', '深睡', '浅睡', '快速眼动', '清醒', '未知'];

        return $arr[$type] ?? '';
    }
}

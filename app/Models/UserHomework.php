<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserHomework extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'homework_id', 'grade_id', 'content', 'score', 'evaluation', 'end_at', 'completed_at', 'status'];

    protected $appends = ['status_str'];

    public function getContentAttribute()
    {
        $content = $this->attributes['content'] ?? '';
        if ($content) {
            return json_decode($content, true);
        }
        return false;
    }

    public function getStatusStrAttribute()
    {
        $value = $this->attributes['status'] ?? 100;

        $array = ['待完成', '已完成', '逾期完成', '未完成'];

        return $array[$value] ?? '';
    }

    public function homework()
    {
        return $this->belongsTo(Homework::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}

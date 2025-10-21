<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserHomework extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'homework_id', 'content', 'score', 'evaluation', 'end_at', 'completed_at', 'status'];

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

        $array = ['待完成', '已完成', '未完成'];

        return $array[$value] ?? '';
    }
}

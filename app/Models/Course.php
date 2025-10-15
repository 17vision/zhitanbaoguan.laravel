<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'duration', 'category', 'difficulty', 'description', 'cover', 'background', 'status'];

    protected $appends = ['category_str', 'difficulty_str', 'status_str'];

    public function getCategoryStrAttribute()
    {
        $value = $this->attributes['category'] ?? 0;

        $array = ['', '睡眠', '专注', '减压'];

        return $array[$value] ?? '';
    }
    public function getDifficultyStrAttribute()
    {
        $value = $this->attributes['difficulty'] ?? 0;

        $array = ['', '初级', '中级', '高级'];

        return $array[$value] ?? '';
    }

    public function getStatusStrAttribute()
    {
        $value = $this->attributes['status'] ?? 100;

        $array = ['待发布', '已发布'];

        return $array[$value] ?? '';
    }

    public function chapters()
    {
        return $this->hasMany(CourseChapter::class);
    }
}

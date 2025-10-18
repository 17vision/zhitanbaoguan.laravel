<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseHomework extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'course_id', 'title', 'content', 'config'];

    public function getConfigAttribute()
    {
        $config = $this->attributes['config'] ?? '';
        if ($config) {
            return json_decode($config, true);
        }
        return false;
    }
}

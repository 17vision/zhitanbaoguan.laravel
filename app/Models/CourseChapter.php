<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseChapter extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'course_id', 'title', 'description', 'background', 'resource_id', 'index'];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
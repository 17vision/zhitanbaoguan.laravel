<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseChapter extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'course_id', 'title', 'description', 'duration', 'background', 'resource_id', 'index'];

    public function getBackgroundAttribute()
    {
        if (isset($this->attributes['background'])) {
            return storageUrl($this->attributes['background']);
        }
        return '';
    }
    
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
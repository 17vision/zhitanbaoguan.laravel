<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Scene extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'scene_category_id', 'name', 'introduction', 'image', 'video', 'tag', 'like_nums', 'collect_nums'];

    public function sceneCategory()
    {
        return $this->belongsTo(SceneCategory::class);
    }

    public function getImageAttribute()
    {
        if (isset($this->attributes['image'])) {
            $image = $this->attributes['image'];
            return storageUrl($image);
        }
        return '';
    }

    public function getVideoAttribute()
    {
        if (isset($this->attributes['video'])) {
            $video = $this->attributes['video'];
            return storageUrl($video);
        }
        return '';
    }
}

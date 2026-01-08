<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Scene extends Model
{
    use HasFactory;

    protected $fillable = ['scene_category_id', 'name', 'introduction', 'image', 'video', 'tag', 'like_nums', 'collect_nums'];

    public function sceneCategory()
    {
        return $this->belongsTo(SceneCategory::class);
    }
}

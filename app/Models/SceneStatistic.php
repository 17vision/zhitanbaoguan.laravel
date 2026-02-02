<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SceneStatistic extends Model
{
    use HasFactory;

    protected $fillable = ['scene_id', 'type', 'user_id', 'duration'];

    public function scene()
    {
        return $this->belongsTo(Scene::class);
    }
}
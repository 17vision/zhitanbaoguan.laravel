<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SceneCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'introduction', 'scene_nums'];
}

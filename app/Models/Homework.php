<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Homework extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'homework_group_id', 'title', 'content', 'config', 'status'];

    public function getConfigAttribute()
    {
        $config = $this->attributes['config'] ?? '';
        if ($config) {
            return json_decode($config, true);
        }
        return false;
    }

    public function group()
    {
        return $this->belongsTo(HomeworkGroup::class);
    }
}

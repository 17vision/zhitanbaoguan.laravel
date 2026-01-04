<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Theme extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'introduction', 'head', 'path', 'like_nums', 'unlike_nums', 'status'];

    public function getHeadAttribute()
    {
        if (isset($this->attributes['head'])) {
            $head = $this->attributes['head'];
            return storageUrl($head);
        }
        return '';
    }

    public function getPathAttribute()
    {
        if (isset($this->attributes['path'])) {
            $path = $this->attributes['path'];
            return storageUrl($path);
        }
        return '';
    }
}

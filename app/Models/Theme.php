<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Theme extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'introduction', 'path', 'color', 'like_nums', 'unlike_nums', 'status'];
    protected $appends = ['status_str'];

    public function getPathAttribute()
    {
        if (isset($this->attributes['path'])) {
            $path = $this->attributes['path'];
            return storageUrl($path);
        }
        return '';
    }

    public function getStatusStrAttribute()
    {
        $value = $this->attributes['status'] ?? 100;

        $array = ['', '可用', '不可用'];

        return $array[$value] ?? '';
    }
}

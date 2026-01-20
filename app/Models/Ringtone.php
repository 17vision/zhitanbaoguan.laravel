<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ringtone extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'introduction', 'thumbnail', 'path', 'status'];

    protected $appends = ['status_str'];
    
    public function getThumbnailAttribute()
    {
        $value = $this->attributes['thumbnail'] ?? '';
        if ($value) {
            return storageUrl(value: $value);
        }
        return '';
    }

    public function getPathAttribute()
    {
        $value = $this->attributes['path'] ?? '';
        if ($value) {
            return storageUrl(value: $value);
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

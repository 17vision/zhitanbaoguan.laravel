<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ringtone extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'introduction', 'thumbnail', 'path', 'status'];

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
}

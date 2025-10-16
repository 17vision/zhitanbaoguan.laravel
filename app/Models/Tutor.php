<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Tutor extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'username', 'avatar', 'introduction'];

    public function getAvatarAttribute()
    {
        if (isset($this->attributes['avatar'])) {
            return storageUrl($this->attributes['avatar']);
        }
        return '';
    }
}

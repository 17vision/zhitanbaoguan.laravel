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
            $dataurl = $this->attributes['avatar'];
            if (Str::startsWith($dataurl, ['http://', 'https://'])) {
                return $dataurl;
            }
            return url($dataurl);
        }
        return '';
    }
}

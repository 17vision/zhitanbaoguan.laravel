<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Scene extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'scene_category_id', 'name', 'introduction', 'image', 'video', 'tag', 'like_nums', 'collect_nums', 'status'];

    protected $appends = ['type_str', 'status_str'];

    public function sceneCategory()
    {
        return $this->belongsTo(SceneCategory::class);
    }

    public function getTypeStrAttribute()
    {
        $type = $this->getAttribute('type');

        if ($type) {
            $arr = ['', '专注', '睡眠', '小憩', '呼吸'];
            $result = '';
            $types = explode(',',  $type);
            foreach($types as $index) {
                $item = $arr[$index] ?? '';
                if ($item) {
                    $result .= ",$item";
                }
            }

            if ($result) {
                return substr($result, 1);
            }
        }
        return '';
    }

    public function getImageAttribute()
    {
        if (isset($this->attributes['image'])) {
            $image = $this->attributes['image'];
            return storageUrl($image);
        }
        return '';
    }

    public function getVideoAttribute()
    {
        if (isset($this->attributes['video'])) {
            $video = $this->attributes['video'];
            return storageUrl($video);
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

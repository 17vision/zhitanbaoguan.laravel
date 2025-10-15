<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'resource_group_id', 'name', 'type', 'path', 'thumbnail', 'remark', 'usage_count'];

    protected $appends = ['type_str'];

    public function getTypeStrAttribute()
    {
        $value = $this->attributes['type'] ?? 0;

        $array = ['', '图片', '视频', '音频', '场景'];

        return $array[$value] ?? '';
    }

    public function getPathAttribute()
    {
        $value = $this->attributes['path'] ?? '';
        if ($value) {
            return storageUrl(value: $value);
        }
        return '';
    }

    public function getThumbnailAttribute()
    {
        $value = $this->attributes['thumbnail'] ?? '';
        if ($value) {
            return storageUrl(value: $value);
        }
        return '';
    }

    public function group()
    {
        return $this->belongsTo(ResourceGroup::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CombineTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['combine_album_id', 'name', 'cover', 'introduction', 'sort', 'status', 'qrcode'];

    protected $appends = ['status_str'];

    public function getStatusStrAttribute()
    {
        if (isset($this->attributes['status'])) {
            $status = $this->attributes['status'];
            if ($status == 1) {
                return '已上线';
            } elseif ($status == 2) {
                return '已下线';
            }
        }
        return '';
    }

    public function getCoverAttribute()
    {
        if (isset($this->attributes['cover'])) {
            $cover = $this->attributes['cover'];
            return pathToOss($cover);
        }
        return '';
    }

    public function getQrcodeAttribute()
    {
        if (isset($this->attributes['qrcode'])) {
            $qrcode = $this->attributes['qrcode'];
            return storageUrl($qrcode);
        }
        return '';
    }

    public function combineAlbum()
    {
        return $this->belongsTo(CombineAlbum::class);
    }

    public function photos()
    {
        return $this->hasMany(CombinePhoto::class);
    }
}

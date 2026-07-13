<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CombineAlbum extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'venue_id', 'name', 'cover', 'introduction', 'sort', 'status'];

    protected $appends = ['status_str'];

    public function getStatusStrAttribute()
    {
        if (isset($this->attributes['status'])) {
            $status = $this->attributes['status'];
            if ($status == 0) {
                return '待上线';
            } elseif ($status == 1) {
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

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function templates()
    {
        return $this->hasMany(CombineTemplate::class);
    }

    public function photos()
    {
        return $this->hasMany(CombinePhoto::class);
    }
}

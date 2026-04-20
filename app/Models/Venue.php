<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Venue extends Model
{
    use HasFactory;
    protected $fillable = [
        'organization_id',
        'name',
        'cover',
        'address',
        'phone',
        'introduction',
        'open_time',
        'close_time',
        'longitude',
        'latitude',
        'qrcode',
        'status'
    ];

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

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function getCoverAttribute()
    {
        if (isset($this->attributes['cover'])) {
            $cover = $this->attributes['cover'];
            return pathToOss($cover);
        }
        return '';
    }

    public function introductions()
    {
        return $this->hasMany(VenueIntroduction::class);
    }

    public function medias()
    {
        return $this->hasMany(VenueMedia::class);
    }
}

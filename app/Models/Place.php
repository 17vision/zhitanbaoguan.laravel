<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Place extends Model
{
    use HasFactory;
    protected $fillable = ['organization_id', 'venue_id', 'parent_id', 'name', 'cover', 'address', 'introduction', 'open_time', 'close_time', 'longitude', 'latitude', 'tag', 'level', 'status'];

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

    public function introductions()
    {
        return $this->hasMany(PlaceIntroduction::class);
    }

    public function medias()
    {
        return $this->hasMany(PlaceMedia::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    // 父权限
    public function father()
    {
        return $this->hasOne(self::class, 'id', 'parent_id');
    }

    // 递归父级
    public function parents()
    {
        return $this->father()->with('parents');
    }

    //子权限
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    // 递归子级
    public function childs()
    {
        return $this->children()->with('childs');
    }
}

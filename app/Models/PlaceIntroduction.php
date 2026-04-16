<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlaceIntroduction extends Model
{
    use HasFactory;

    protected $fillable = ['place_id', 'name', 'content', 'voice', 'status'];
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

    public function getVoiceAttribute()
    {
        if (isset($this->attributes['voice'])) {
            $voice = $this->attributes['voice'];
            return pathToOss($voice);
        }
        return '';
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }
}

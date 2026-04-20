<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlaceMedia extends Model
{
    use HasFactory;

    protected $fillable = ['place_id', 'type', 'name', 'path', 'thumbnail', 'duration', 'sort', 'status'];
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
}

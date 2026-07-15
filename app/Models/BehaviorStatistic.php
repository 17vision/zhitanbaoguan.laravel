<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BehaviorStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'venue_id',
        'user_id',
        'date',
        'type',
        'target_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected $appends = ['type_str'];

    public function getTypeStrAttribute()
    {
        if (isset($this->attributes['type'])) {
            $type = $this->attributes['type'];
            if ($type == 1) {
                return '打开小程序';
            } elseif ($type == 2) {
                return '使用讲解';
            }
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

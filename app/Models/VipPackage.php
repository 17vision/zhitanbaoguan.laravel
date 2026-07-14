<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class VipPackage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'venue_id',
        'package_name',
        'description',
        'price',
        'original_price',
        'is_recommend',
        'is_only_once',
        'combine_count',
        'chinese_explain',
        'multi_explain',
        'sort',
        'status',
    ];

    protected $appends = ['status_str'];

    public function getStatusStrAttribute()
    {
        if (isset($this->attributes['status'])) {
            $status = $this->attributes['status'];
            if ($status == 0) {
                return '待上架';
            } elseif ($status == 1) {
                return '已上架';
            } elseif ($status == 2) {
                return '已下架';
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

    public function orders()
    {
        return $this->hasMany(VipOrder::class);
    }
}

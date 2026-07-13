<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CombinePhoto extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['organization_id', 'venue_id', 'user_id', 'combine_album_id', 'combine_template_id', 'cover', 'photo', 'product_img', 'combine_date'];

    protected $casts = [
        'combine_date' => 'date',
    ];

    public function getCoverAttribute()
    {
        if (isset($this->attributes['cover'])) {
            $cover = $this->attributes['cover'];
            return pathToOss($cover);
        }
        return '';
    }

    public function getPhotoAttribute()
    {
        if (isset($this->attributes['photo'])) {
            $photo = $this->attributes['photo'];
            return pathToOss($photo);
        }
        return '';
    }

    public function getProductImgAttribute()
    {
        if (isset($this->attributes['product_img'])) {
            $productImg = $this->attributes['product_img'];
            return pathToOss($productImg);
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

    public function combineAlbum()
    {
        return $this->belongsTo(CombineAlbum::class);
    }

    public function combineTemplate()
    {
        return $this->belongsTo(CombineTemplate::class);
    }
}

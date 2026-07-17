<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class CombinePhoto extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 0; // 待合成
    public const STATUS_PROCESSING = 1; // 合成中
    public const STATUS_SUCCESS = 2; // 成功
    public const STATUS_FAILED = 3; // 失败

    protected $fillable = [
        'organization_id',
        'venue_id',
        'user_id',
        'combine_album_id',
        'combine_template_id',
        'cover',
        'photo',
        'product_img',
        'status',
        'failreason',
        'combine_date',
    ];

    protected $casts = [
        'combine_date' => 'date',
        'status' => 'integer',
    ];

    /**
     * 标记合成失败并返还次数（已失败过则不重复返还）
     */
    public function markFailed(string $reason): void
    {
        $shouldRefund = (int) $this->status !== self::STATUS_FAILED;

        $this->status = self::STATUS_FAILED;
        $this->failreason = $reason;
        $this->save();

        if ($shouldRefund) {
            $this->refundCombineCount();
        }
    }

    /**
     * 合成失败返还次数：
     * 优先 vip_users 有记录则 +1；否则按 venue_id+user_id+combine_date 找 user_receives +1
     */
    public function refundCombineCount(): void
    {
        DB::transaction(function () {
            $vipUser = VipUser::query()
                ->where('user_id', $this->user_id)
                ->where('venue_id', $this->venue_id)
                ->lockForUpdate()
                ->first();

            if ($vipUser) {
                $vipUser->increment('combine_count');

                return;
            }

            if (!$this->combine_date) {
                return;
            }

            $receive = UserReceive::query()
                ->where('user_id', $this->user_id)
                ->where('venue_id', $this->venue_id)
                ->whereDate('date', $this->combine_date)
                ->lockForUpdate()
                ->first();

            if ($receive) {
                $receive->increment('combine_count');
            }
        });
    }

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

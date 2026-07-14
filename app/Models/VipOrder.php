<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class VipOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'vip_package_id',
        'number',
        'total_amount',
        'pay_amount',
        'payment_type',
        'client_type',
        'payment_number',
        'paid_at',
        'refund_at',
        'closed_at',
        'status',
        'refund_status',
        'user_refund_status',
        'user_refund_reason',
        'refund_reject_reason',
        'combine_count',
        'chinese_explain',
        'multi_explain',
        'trade_info',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'refund_at' => 'datetime',
        'closed_at' => 'datetime',
        'trade_info' => 'array',
    ];

    protected $appends = ['status_str', 'refund_status_str'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->number) {
                $model->number = static::build_number();
            }
        });
    }

    public static function build_number()
    {
        $number = date('YmdHis') . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        if (!static::query()->where('number', $number)->exists()) {
            return $number;
        }
        return static::build_number();
    }

    public function getStatusStrAttribute()
    {
        if (isset($this->attributes['status'])) {
            $status = $this->attributes['status'];
            if ($status == 1) {
                return '待支付';
            } elseif ($status == 2) {
                return '已支付';
            } elseif ($status == 3) {
                return '已退款';
            } elseif ($status == 0) {
                return '已关闭';
            }
        }
        return '';
    }

    public function getRefundStatusStrAttribute()
    {
        $refund_status = $this->attributes['refund_status'] ?? null;
        $user_refund_status = $this->attributes['user_refund_status'] ?? null;

        if ($user_refund_status == 1) {
            return '退款申请中';
        }

        if ($refund_status == 1) {
            return '退款中';
        }

        if ($refund_status == 2) {
            return '退款已完成';
        }

        if ($user_refund_status == 4) {
            return '退款被驳回';
        }
        return '';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vipPackage()
    {
        return $this->belongsTo(VipPackage::class);
    }
}

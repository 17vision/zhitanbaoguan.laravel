<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'device_id',
        'number',
        'name',
        'total_amount',
        'pay_amount',
        'payment_type',
        'payment_number',
        'paid_at',
        'refund_at',
        'closed_at',
        'play_begin_at',
        'play_end_at',
        'status',
        'order_status',
        'refund_status',
        'user_refund_status',
        'user_refund_reason',
        'refund_reject_reason',
    ];

    protected $appends = ['status_str', 'order_status_str', 'use_status', 'refund_status_str'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        // 监听模型创建事件，在写入数据库之前触发
        static::creating(function ($model) {
            if (!$model->number) {
                $model->number = static::build_number();
            }
        });
    }

    // 生成流水 number
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

    public function getOrderStatusStrAttribute()
    {
        if (isset($this->attributes['order_status'])) {
            $order_status = $this->attributes['order_status'];

            // $status = $this->attributes['status'] ?? null;
            // if ($status && $status == 3) {
            //     return '已退款';
            // }

            // if ($status === 0) {
            //     return '已关闭';
            // }

            if ($order_status == 1) {
                return '待付款';
            } elseif ($order_status == 2) {
                return '待体验';
            } elseif ($order_status == 3) {
                return '体验中';
            } elseif ($order_status == 4) {
                return '已体验';
            } elseif ($order_status == 0) {
                return '已关闭';
            }
        }
        return '';
    }

    public function getUseStatusAttribute()
    {
        if (isset($this->attributes['status']) && isset($this->attributes['order_status'])) {
            $status = $this->attributes['status'];
            $order_status = $this->attributes['order_status'];

            if ($status == 0) {
                return '已关闭';
            }

            if ($order_status == 2) {
                return '待体验';
            }

            if ($order_status == 3) {
                return '体验中';
            }

            if ($order_status == 4) {
                return '已体验';
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
}

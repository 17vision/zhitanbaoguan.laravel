<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\SerializeDate;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use SoftDeletes;
    use HasRoles;
    use SerializeDate;

    protected $fillable = [
        'wx_unionid', 'wxmini_openid', 'wxmini_session_key', 'wxapp_openid', 'wxgzh_openid', 'viewid', 'account', 'password',
        'nickname', 'phone_prefix', 'phone', 'gender', 'avatar', 'email', 'qq', 'wechat', 'birthday', 'height', 'weight', 'age', 'body_fat_pct','province', 'city', 'town', 'address',
        'position', 'signature', 'referer', 'territory_ip', 'register_ip', 'remember_token', 'last_login_at', 'email_verified_at'
    ];

    protected $hidden = [
        'wx_unionid', 'wxmini_openid', 'wxmini_session_key', 'wxapp_openid', 'wxgzh_openid', 'viewid', 'account', 'password', 'remember_token',
    ];

    protected $casts = [
        'last_login_at' => 'datetime', 'email_verified_at' => 'datetime'
    ];

    protected $appends = ['gender_str'];

    public function getAvatarAttribute()
    {
        if (isset($this->attributes['avatar'])) {
            $dataurl = $this->attributes['avatar'];
            if (Str::startsWith($dataurl, ['http://', 'https://'])) {
                return $dataurl;
            }
            return url($dataurl);
        }
        return '';
    }

    public function getGenderStrAttribute()
    {
        if (isset($this->attributes['gender'])) {
            $gender = $this->attributes['gender'];
            $arr = ['', '男', '女'];

            if (isset($arr[$gender])) {
                return $arr[$gender];
            }
        }
        return '';
    }

    public function userExtend()
    {
        return $this->hasOne(UserExtend::class);
    }

    protected static function boot()
    {
        parent::boot();
        // 监听模型创建事件，在写入数据库之前触发
        static::creating(function ($model) {
            // 如果模型的 viewid 字段为空
            if (!$model->viewid) {
                // 调用 build_viewid 来生成 view id
                $model->viewid = static::build_viewid();
            }

            if (!$model->nickname) {
                $model->nickname = '小伙伴-' . $model->viewid;
            }

            if (!$model->signature) {
                $model->signature = static::randomSignature();
            }
        });
    }

    protected static function build_viewid()
    {
        $prefix = rand(1, 9);
        $prefix *= 10000000;
        $difference = rand(1, 9999999);
        $viewid = $prefix + $difference;

        if ($viewid > 99999999) {
            static::build_viewid();
        }

        if (!static::query()->where('viewid', $viewid)->exists()) {
            return $viewid;
        }
        static::build_viewid();
    }

    public function userHealths()
    {
        return $this->hasMany(UserHealth::class);
    }

    protected static function randomSignature()
    {
        $words = [
            '烟花飞腾的时候，火焰掉入海中。遗忘就和记得一样，是送给彼此最好的纪念。爱从来都不是归宿，也不是彼此最好的救赎。',
            '仿佛有痛楚。如果我晕眩。那是因为幻觉丰盛，能量薄弱。足以支持我对你的迷恋，不够支持我们的快乐。',
            '天没有边，没有界。心是花园也是荒野。光阴在花绽开中消亡，歌舞却永不停下。将一片云纱与你。敢不敢，愿不愿，一起飞越长空。',
            '一道电光劈开天幕。苍穹间有笔疾书，叫做战胜脆弱。',
            '虚幻之物对应着冥冥之路。',
            '美的事物是永恒的喜悦。',
            '舞低杨柳楼心月，歌尽桃花扇底风。从别后，忆相逢，几回魂梦与君同。',
            '车如流水马如龙，花月正春风。',
            '闲引鸳鸯香径里，手挼红杏蕊。',
            '夏雨雪，天地合，乃敢与君绝。',
            '一百年前，你不是你，我不是我。眼泪是真的，悲哀是假的。本来没因果。一百年后，没有你，也没有我。'
        ];

        return Arr::random($words);
    }

    public function visits()
    {
        return visits($this);
    }
}

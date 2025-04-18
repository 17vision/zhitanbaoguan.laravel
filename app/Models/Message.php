<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'type', 'title', 'content', 'messageable_id', 'messageable_type', 'status', 'readed_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取父级的对象
     */
    public function messageable(): MorphTo
    {
        return $this->morphTo();
    }
}

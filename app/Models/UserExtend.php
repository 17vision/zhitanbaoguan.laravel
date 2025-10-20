<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserExtend extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'admin_role', 'admin_lock', 'course_like_count', 'course_collect_count', 'introduction'];
}
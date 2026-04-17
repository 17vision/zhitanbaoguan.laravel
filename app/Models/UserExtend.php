<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserExtend extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'admin_role', 'admin_lock', 'introduction'];
}
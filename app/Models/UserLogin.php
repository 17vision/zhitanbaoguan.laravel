<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserLogin extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'database/migrations', 'ip', 'latitude', 'longitude', 'city', 'citycode', 'login_at'];
}

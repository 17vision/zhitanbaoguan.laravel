<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserLogin extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'ip', 'latitude', 'longitude', 'city', 'citycode', 'system', 'device_model', 'device_type', 'os_name', 'login_at'];
}

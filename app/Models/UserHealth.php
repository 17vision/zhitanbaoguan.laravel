<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserHealth extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'heart_rate', 'blood_oxygen', 'systolic', 'diastolic'];
}
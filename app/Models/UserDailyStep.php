<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserDailyStep extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'date', 'steps', 'calories', 'distance'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

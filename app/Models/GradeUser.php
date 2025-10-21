<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class GradeUser extends Model
{
    use HasFactory;

    protected $fillable = ['grade_id', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

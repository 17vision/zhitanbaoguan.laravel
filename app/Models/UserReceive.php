<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserReceive extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'venue_id',
        'user_id',
        'date',
        'combine_count',
        'explain_count',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

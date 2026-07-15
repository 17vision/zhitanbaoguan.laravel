<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class VipUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'venue_id',
        'user_id',
        'combine_count',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
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

    public function isVip(): bool
    {
        return (int) $this->combine_count > 0
            || ($this->expired_at && $this->expired_at->isFuture());
    }

    public function canExplain(): bool
    {
        return $this->expired_at && $this->expired_at->isFuture();
    }
}

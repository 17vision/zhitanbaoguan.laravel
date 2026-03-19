<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'workflow_id',
        'pay_amount',
        'play_begin_at',
        'play_end_at',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}

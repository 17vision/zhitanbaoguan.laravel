<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BrainMachineData extends Model
{
    use HasFactory;

    protected $fillable = ['concentration_level', 'relaxation_level'];
}

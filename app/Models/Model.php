<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;
use App\Traits\SerializeDate;

class Model extends BaseModel
{
    use SerializeDate;

    protected $way;
}

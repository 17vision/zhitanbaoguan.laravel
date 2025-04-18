<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SRole;
use App\Traits\SerializeDate;

class Role extends SRole
{
    use SerializeDate;
}

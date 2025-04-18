<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SPermission;

class Permission extends SPermission
{
    //子权限
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    // 递归子级
    public function childs()
    {
        return $this->children()->with('childs');
    }

    // 父权限
    public function father()
    {
        return $this->hasMany(self::class, 'id', 'parent_id');
    }

    // 递归父级
    public function parents()
    {
        return $this->father()->with('parents');
    }
}

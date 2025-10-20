<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class HomeworkGroup extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'description', 'parent_id', 'index'];

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
        return $this->hasOne(self::class, 'id', 'parent_id');
    }

    // 递归父级
    public function parents()
    {
        return $this->father()->with('parents');
    }
}

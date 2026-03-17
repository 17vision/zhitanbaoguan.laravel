<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'user_id', 'create_user_id', 'workflow_group_id', 'name', 'cover', 'description', 'price', 'is_public', 'status', 'version'];

    protected $appends = ['status_str'];

    protected $connection = 'dkj';

    protected $table = 'workflows';

    public function getStatusStrAttribute()
    {
        if (isset($this->attributes['status']) && $this->attributes['status']) {
            $status = $this->attributes['status'];

            if ($status == 1) {
                return '待上线';
            } elseif ($status == 2) {
                return '已上线';
            }
        }
        return '-';
    }

    public function getCoverAttribute()
    {
        if (isset($this->attributes['cover']) && $this->attributes['cover']) {
            return  'https://dkj.17vision.com/' . $this->attributes['cover'];
        }
        return '';
    }
}

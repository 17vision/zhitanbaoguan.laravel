<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'user_id', 'create_user_id', 'workflow_group_id', 'name', 'cover', 'description', 'price', 'is_public', 'status', 'list_status', 'version'];

    protected $appends = ['status_str', 'list_status_str'];

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

    public function getListStatusStrAttribute()
    {
        if (isset($this->attributes['list_status']) && $this->attributes['list_status']) {
            $list_status = $this->attributes['list_status'];

            if ($list_status == 1) {
                return '待上架';
            } elseif ($list_status == 2) {
                return '已上架';
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

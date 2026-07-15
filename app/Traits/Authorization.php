<?php

namespace App\Traits;

use App\Models\User;
use App\Models\VipUser;
use Carbon\Carbon;

trait Authorization
{
    protected function auth($id, $createToken = true)
    {
        $user = User::with(['userExtend:id,user_id,introduction'])->where('id', $id)->first();

        if ($createToken) {
            $user['token'] = $user->createToken('auth')->plainTextToken;
        }

        $user['in_days'] = Carbon::now()->diffInDays($user->created_at);

        $user['is_vip'] = VipUser::query()
            ->where('user_id', $id)
            ->where(function ($query) {
                $query->where('combine_count', '>', 0)
                    ->orWhere('expired_at', '>', now());
            })
            ->exists();

        return $user;
    }
}

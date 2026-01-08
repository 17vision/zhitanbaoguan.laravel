<?php

namespace App\Traits;

use App\Models\User;
use Carbon\Carbon;

trait Authorization
{
    protected function auth($id, $createToken = true)
    {
        $user = User::with(['userExtend:id,user_id,course_like_count,course_collect_count,introduction', 'userHealths'])->where('id', $id)->first();

        if ($createToken) {
            $user['token'] = $user->createToken('auth')->plainTextToken;
        }

        $user['in_days'] = Carbon::now()->diffInDays($user->created_at);

        return $user;
    }
}

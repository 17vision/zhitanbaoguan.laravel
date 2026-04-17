<?php

namespace App\Traits;

use App\Models\User;
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

        return $user;
    }
}

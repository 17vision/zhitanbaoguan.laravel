<?php

namespace App\Traits;

use App\Models\User;

trait Authorization
{
    protected function auth($id, $createToken = true)
    {
        $user = User::with(['userExtend:id,user_id,course_like_count,course_collect_count,introduction'])->where('id', $id)->first();

        if ($createToken) {
            $user['token'] = $user->createToken('auth')->plainTextToken;
        }

        return $user;
    }
}

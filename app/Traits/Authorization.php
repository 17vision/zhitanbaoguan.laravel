<?php

namespace App\Traits;

use App\Models\User;

trait Authorization
{
    protected function auth($id, $createToken = true)
    {
        $user = User::with(['userExtend:id,user_id'])->where('id', $id)->first();

        if ($createToken) {
            $user['token'] = $user->createToken('auth')->plainTextToken;
        }

        return $user;
    }
}

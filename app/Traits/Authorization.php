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

        $canCombine = $user->combine_count > 0;
        $canExplain = ($user->chinese_explain_expire && $user->chinese_explain_expire->isFuture())
            || ($user->multi_explain_expire && $user->multi_explain_expire->isFuture());
        $user['is_vip'] = $canCombine || $canExplain;

        return $user;
    }
}

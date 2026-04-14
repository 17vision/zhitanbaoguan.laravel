<?php

namespace App\Observers;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserExtend;
use App\Models\Message;
use App\Models\Role;

class UserObserver
{
    public function created(User $user)
    {
        try {
            UserExtend::create([
                'user_id' => $user->id,
            ]);

            Message::create([
                'user_id' => $user->id,
                'title' => '欢迎注册',
                'content' => '[nickname]，花径不曾缘客扫，蓬门今始为君开。',
                'messageable_id' => $user->id,
                'messageable_type' => get_class($user)
            ]);

            $role = Role::where('name', 'default')->first();
            if ($role) {
                $user->assignRole($role);
                UserExtend::where('id', $user->id)->update(['admin_role' => 'default']);
            }
        } catch (Exception $e) {
            Log::channel('user')->error('UserObserver', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }
}

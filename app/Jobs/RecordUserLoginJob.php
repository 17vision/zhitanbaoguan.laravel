<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UserLogin;
use App\Models\User;

class RecordUserLoginJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;          // 失败重试 3 次
    public $backoff = [2, 10];  // 第一次 2s，第二次 10s

    private $user_id;
    private $client_type;
    private $ip;
    private $login_at;

    public function __construct($user_id, $client_type, $ip, $login_at)
    {
        $this->user_id = $user_id;
        $this->client_type = $client_type;
        $this->ip = $ip;
        $this->login_at = $login_at;
    }

    public function handle(): void
    {
        $today = $this->login_at->copy()->startOfDay();

        $userLogin = UserLogin::firstOrCreate(
            [
                'user_id' => $this->user_id,
                ['login_at', '>=', $today],
                ['login_at', '<', $today->copy()->addDay()],
            ],
            [
                'client_type' => $this->client_type,
                'ip'       => $this->ip,
                'login_at' => $this->login_at,
            ]
        );

        // 只有真正插入时才刷新 users 表
        if ($userLogin->wasRecentlyCreated) {
            User::where('id', $this->user_id)->update(['last_login_at' => $this->login_at]);
        }
    }
}

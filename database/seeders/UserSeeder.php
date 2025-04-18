<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $phone = '13671638524';
        if (!User::where('phone', $phone)->exists()) {
            $remember_token = Str::random(10);
            $data = [
                'account' => $phone,
                'password' => Hash::make('111111'),
                'nickname' => '七月羽歌',
                'phone' => $phone,
                'gender' => 1,
                'email' => 'zhoulin@xiangrong.pro',
                'email_verified_at' => Carbon::now()->toDateTimeString(),
                'remember_token' => $remember_token
            ];
            User::create($data);
        }

        // User::factory()->count(50)->create();
    }
}

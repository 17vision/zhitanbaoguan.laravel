<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Jobs\RecordUserLoginJob;

class UserLogin
{
    // 用来记录用户登录的特征
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('api')->user();
        if (isset($user) && $user) {
            $client_type = $request->input('client_type', null);

            RecordUserLoginJob::dispatch($user['id'], $client_type, $request->ip(), Carbon::now())->onQueue('login');
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserGet
{
    // 补充一些信息 （出此下策实属无奈啊）
    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(function() {
            return Auth::guard('api')->user();
        });
        return $next($request);
    }
}

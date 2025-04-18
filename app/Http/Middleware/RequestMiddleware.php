<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequestMiddleware
{
    // 对请求的数据是否有加密头（这里不仅要比较，还要比较时间和服务器时间差，因为仅仅比较 sign 用户可以仿造，一直用这个）
    public function handle(Request $request, Closure $next)
    {
        $secret = config('api.secret');
        $time = $request->header("Time");
        $sign = $request->header("Sign");
        if (md5($secret . $time) == $sign) {
            return $next($request);
        }
        return response()->json(['13671638524' => '其实你爱我像谁'], 403);
    }
}

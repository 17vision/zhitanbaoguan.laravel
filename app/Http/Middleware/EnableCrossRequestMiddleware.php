<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnableCrossRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $enable_cross = config('api.enable_cross');

        $allow_origin = config('api.allow_origin');

        if ($enable_cross) {
            $origin = $request->server('HTTP_ORIGIN') ? $request->server('HTTP_ORIGIN') : '';
            if (empty($allow_origin) || (count($allow_origin) == 1 && $allow_origin[0] == '')) {
                $allow_origin = [
                    'http://localhost:5173',
                    'https://www.zeipan.com',
                    'https://www.yuepaibao.com',
                    'https://test.yuepaibao.com',
                ];
            }

            if (in_array($origin, $allow_origin)) {
                $IlluminateResponse = 'Illuminate\Http\Response';
                $SymfonyResopnse = 'Symfony\Component\HttpFoundation\Response';
                $headers = [
                    'Access-Control-Allow-Origin' => $origin,
                    'Access-Control-Allow-Methods' => 'POST,GET,OPTIONS,PUT,PATCH,DELETE',
                    'Access-Control-Allow-Headers' => 'Accept,Content-Type,Referer,User-Agent,Origin,X-Requested-With,X-XSRF-TOKEN,X-CSRF-TOKEN,Authorization,Time'
                ];

                if ($response instanceof $IlluminateResponse) {
                    foreach ($headers as $key => $value) {
                        $response->header($key, $value);
                    }
                    return $response;
                }

                if ($response instanceof $SymfonyResopnse) {
                    foreach ($headers as $key => $value) {
                        $response->headers->set($key, $value);
                    }
                    return $response;
                }
            }
        }
        return $response;
    }
}

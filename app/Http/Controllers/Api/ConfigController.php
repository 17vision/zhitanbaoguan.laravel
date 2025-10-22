<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function show(Request $request)
    {
        $data = [
            'name' => '观息空间',
            'likes' => 5,
            'collects' => 10,
            'is_check' => env('WX_CHECK', 1),
            'version' => 1.0
        ];

        return response()->json($data);
    }
}

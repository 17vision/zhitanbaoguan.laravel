<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class DashboardController extends Controller
{
    public function basicInfo()
    {
        return response()->json([]);
    }

    public function singleData(Request $request)
    {
        return response()->json([]);
    }

    public function viewData(Request $request)
    {
        return [];
    }
}

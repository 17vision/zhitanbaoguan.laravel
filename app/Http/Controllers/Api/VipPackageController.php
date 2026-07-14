<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VipPackage;

class VipPackageController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'venue_id' => 'required|integer|exists:venues,id',
        ], [], [
            'venue_id' => '场馆 id',
        ]);

        $packages = VipPackage::query()
            ->where('venue_id', $request->input('venue_id'))
            ->where('status', 1)
            ->orderBy('sort')
            ->orderByDesc('id')
            ->get();

        return response()->json($packages);
    }
}

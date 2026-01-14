<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrainMachineData;
use Illuminate\Http\Request;

class BrainMachineDataController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'limit' => 'filled|integer'
        ], [], [
            'limit' => __('messages.limit'),
        ]);

        $limit = $request->input('limit', 30);

        $brainMachineDatas = BrainMachineData::query()->orderByDesc('id')->paginate($limit);

        return response()->json($brainMachineDatas);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BrainMachineData;
use Illuminate\Http\Request;

class BrainMachineDataController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'concentration_level' => 'required|integer',
            'relaxation_level' => 'required|integer',
        ],[],[
            'concentration_level' => '专注度',
            'relaxation_level' => '放松度',
        ]);

        $data = $request->only(['concentration_level', 'relaxation_level']);

        $result = BrainMachineData::create($data);

        return response()->json($result);
    }
}

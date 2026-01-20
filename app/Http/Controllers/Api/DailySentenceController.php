<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\DailySentence;

class DailySentenceController extends Controller
{
    public function detail(Request $request)
    {
        $request->validate([
            'date' => 'filled|date'
        ],[],[
            'date' => '日期'
        ]);

        $date = $request->input('date');

        if (!$date) {
            $date = Carbon::now()->toDateString();
        }

        $dailySentence = DailySentence::query()->where('date', $date)->first();

        return response()->json($dailySentence);
    }
}

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
            'date' => 'required_without:begin_date|date',
            'begin_date' => 'required_without:date|date',
            'end_date' => 'required_without:date|date|after:begin_date',
        ], [], [
            'date' => '日期',
            'begin_date' => '开始日期',
            'end_date' => '结束日期',
        ]);

        $date = $request->input('date');
        $begin_date = $request->input('begin_date');
        $end_date = $request->input('end_date');

        $query = DailySentence::query();
        if ($date) {
            $dailySentences = $query->where('date', $date)->get();
        } else {
            $dailySentences = $query->whereBetween('date', [$begin_date, $end_date])->get();
        }
        return response()->json($dailySentences);
    }
}

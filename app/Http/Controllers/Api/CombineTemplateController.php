<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CombineTemplate;

class CombineTemplateController extends Controller
{
    public function detail(Request $request, $id)
    {
        $template = CombineTemplate::where('id', $id)
            ->where('status', 1)
            ->with(['combineAlbum'])
            ->first();

        return response()->json($template);
    }
}

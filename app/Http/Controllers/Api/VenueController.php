<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Venue;

class VenueController extends Controller
{
    public function detail(Request $request, $id)
    {
        $venue = Venue::where('id', $id)->with([
            'organization:id,name',
            'introductions' => function ($query) {
                $query->whereIn('status', [0, 1])
                    ->orderBy('status', 'asc')
                    ->orderBy('sort')
                    ->orderByDesc('id');
            },
            'medias',
        ])->first();

        return response()->json($venue);
    }
}

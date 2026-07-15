<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserReceive;
use App\Models\Venue;
use Illuminate\Http\Request;

class UserReceiveController extends Controller
{
    public function receive(Request $request)
    {
        $request->validate([
            'venue_id' => 'required|integer|exists:venues,id',
        ], [], [
            'venue_id' => '场馆 id',
        ]);

        $user = $request->user();
        $venue_id = $request->input('venue_id');
        $date = now()->toDateString();

        $exists = UserReceive::query()
            ->where('venue_id', $venue_id)
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'success']);
        }

        $venue = Venue::query()->where('id', $venue_id)->first();
        if (!$venue) {
            return response()->json(['message' => '场馆不存在'], 403);
        }

        UserReceive::create([
            'organization_id' => $venue->organization_id,
            'venue_id' => $venue_id,
            'user_id' => $user->id,
            'date' => $date,
            'combine_count' => 1,
            'explain_count' => 1,
        ]);

        return response()->json(['message' => 'success']);
    }
}

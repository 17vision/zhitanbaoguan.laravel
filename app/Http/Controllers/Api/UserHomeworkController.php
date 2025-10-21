<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserHomework;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserHomeworkController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
        ], [], [
            'limit' => '单页显示条数',
            'page' => '当前页',
        ]);

        $limit = $request->input('limit', 20);

        $user = $request->user();

        $userHomeworks = UserHomework::query()->where('user_id', $user->id)->with(['homework.group.parents'])->simplePaginate($limit);

        $groups = [];

        foreach ($userHomeworks as $userHomework) {
            foreach ($userHomework['homework']['group'] as $item) {
                if (!isset($groups[$item['id']])) {
                    $groups[$item['id']] = [
                        'name' => $item['name'],
                        'description' => $item['description'],
                        'id' => $item['id']
                    ];
                }
            }
        }
        return response()->json(array_values($groups));
    }

    public function detail(Request $request, $id)
    {
        $query = UserHomework::query()->with(['homework']);

        $query->whereHas('homework', function ($query) use ($id) {
            $query->where('homework_group_id', $id);
        });

        $userHomeworks = $query->get();

        return response()->json($userHomeworks);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|min:1',
            'content' => 'required|json',
        ], [], [
            'id' => '用户作业 id',
            'content' => '作业',
        ]);

        $user = $request->user();

        $userHomework = UserHomework::query()->where('id', $request->id)->first();
        if (!$userHomework) {
            return response()->json(['message' => '作业不存在']);
        }

        if ($userHomework->user_id != $user->id) {
            return response()->json(['message' => '只有自己才能提交作业']);
        }

        if ($userHomework->status != 0) {
            return response()->json(['message' => '作业状态不正确']);
        }

        if (Carbon::now()->lt($userHomework->end_at)) {
            $status = 1;
        } else {
            $status = 2;
        }

        $userHomework->update([
            'content' => $request->content,
            'completed_at' => Carbon::now()->toDateTimeString(),
            'status' => $status
        ]);

        return response()->json($userHomework);
    }
}

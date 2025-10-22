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
            $item = $userHomework['homework']['group'];
            if (!isset($groups[$item['id']])) {
                $groups[$item['id']] = [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'id' => $item['id'],
                    'status' => [
                        [
                            'status' => $userHomework['status'],
                            'status_str' => $userHomework['status_str']
                        ]
                    ]
                ];
            } else {
                $groups[$item['id']]['status'][] = [
                    'status' => $userHomework['status'],
                    'status_str' => $userHomework['status_str']
                ];
            }
        }

        // 待完成 完成中 已完成 逾期完成

        foreach ($groups as &$group) {
            $status0 = 0;
            $status1 = 0;
            $status2 = 0;

            foreach ($group['status'] as $status) {
                if ($status['status'] == 0) {
                    $status0++;
                }

                if ($status['status'] == 1) {
                    $status1++;
                }

                if ($status['status'] == 2) {
                    $status2++;
                }
            }

            if ($status0 == count($group['status'])) {
                $group['status'] = 0;
                $group['status_str'] = '待完成';
                continue;
            }

            if ($status1 == count($group['status'])) {
                $group['status'] = 2;
                $group['status_str'] = '已完成';
                continue;
            }

            if($status0 > 0) {
                $group['status'] = 1;
                $group['status_str'] = '完成中';
                continue;
            }

            $group['status'] = 3;
            $group['status_str'] = '逾期完成';
        }

        return response()->json(array_values($groups));
    }

    public function detail(Request $request, $id)
    {
        $user = $request->user();

        $query = UserHomework::query()->with(['homework.group']);

        $query->where('user_id', $user->id)->whereHas('homework', function ($query) use ($id) {
            $query->where('homework_group_id', $id);
        });

        $userHomeworks = $query->orderBy('end_at', 'asc')->get();

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

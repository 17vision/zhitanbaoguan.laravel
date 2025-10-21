<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradeUser;
use App\Models\Homework;
use App\Models\UserHomework;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserHomeworkController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
            'homework_id' => 'filled|integer',
        ], [], [
            'page' => '当前页',
            'limit' => '单页显示条数',
            'homework_id' => '作业 id',
        ]);

        $limit = $request->input('limit', 30);

        $homework_id = $request->homework_id;

        $query = UserHomework::query()->with(['homework', 'user:id,nickname']);

        if ($homework_id) {
            $query->where('homework_id', $homework_id);
        }

        $userHomeworks = $query->paginate($limit);

        return response()->json($userHomeworks);
    }

    public function store(Request $request)
    {
        $request->validate([
            'homework_id' => 'required|integer|min:1',
            'user_id' => 'required_without:grade_id|integer|min:1',
            'grade_id' => 'required_without:user_id|integer|min:1',
            'end_at' => 'required|date',
        ], [], [
            'homework_id' => '作业 id',
            'user_id' => '用户 id',
            'grade_id' => '班级 id',
            'end_at' => '结束时间',
        ]);

        $data = $request->only(['homework_id', 'end_at']);

        $user_id = $request->user_id;

        $grade_id = $request->grade_id;

        if (!Carbon::now()->addHours(1)->lte($data['end_at'])) {
            return response()->json(['message' => '结束时间必须大于当前时间 1 小时'], 403);
        }

        if (!Homework::query()->where('id', $data['homework_id'])->exists()) {
            return response()->json(['message' => '作业不存在'], 403);
        }

        if ($user_id) {
            if (UserHomework::query()->where('homework_id', $data['homework_id'])->where('user_id', $user_id)->exists()) {
                return response()->json(['message' => '该用户已分配该作业'], 403);
            }
            $uids = [$user_id];
        } else {
            $uids = GradeUser::query()->where('grade_id', $grade_id)->pluck('user_id')->toArray();

            $exitUids = UserHomework::query()->where('homework_id', $data['homework_id'])->whereIn('user_id', $uids)->pluck('user_id')->toArray();

            $uids = array_diff($uids, $exitUids);

            $updates = UserHomework::query()->where('homework_id', $data['homework_id'])->whereIn('user_id', $exitUids)->update(['end_at' => $data['end_at']]);

            if (empty($uids)) {
                return response()->json(['message' => '班级不存在或班级没有成员没分配'], 403);
            }
        }

        $create = [];
        foreach ($uids as $uid) {
            $data['user_id'] = $uid;
            $create[] = UserHomework::create($data);
        }

        return response()->json(['create' => $create, 'updates' => $updates ?? null]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '作业 id',
        ]);

        $id = $request->id;

        $useHomework = UserHomework::query()->where('id', $id)->first();
        if (!$useHomework) {
            return response()->json(['message' => '作业不存在'], 403);
        }

        $useHomework->delete();

        return response()->json($useHomework);
    }
}

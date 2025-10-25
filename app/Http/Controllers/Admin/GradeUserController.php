<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;
use App\Models\GradeUser;
use App\Models\User;
use App\Models\UserHomework;

class GradeUserController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
            'grade_id' =>  'required|integer|min:1',
            'name' => 'filled|string',
        ], [], [
            'page' => '当前页',
            'limit' => '单页显示条数',
            'grade_id' =>  'required|integer|min:1',
            'name' => '名称',
        ]);

        $limit = $request->input('limit', 30);

        $name = $request->name;

        $grade_id = $request->grade_id;

        $query = GradeUser::query()->with(['user'])->where('grade_id', $grade_id);

        if ($name) {
            $query->whereHas('name', function ($query) use ($name) {
                $query->where('nickname', 'like', '%' . $name . '%');
            });
        }

        $grades = $query->paginate($limit);

        return response()->json($grades);
    }

    public function detail(Request $request, $id)
    {
        $grade = GradeUser::query()->with(['user'])->where('id', $id)->first();

        return response()->json($grade);
    }

    public function store(Request $request)
    {
        $request->validate([
            'grade_id' =>  'required|integer|min:1',
            'user_ids' =>  'required|string',
        ], [], [
            'grade_id' => '班级 id',
            'user_ids' => '用户 id 列表',
        ]);

        $data = $request->only(['grade_id']);

        $user_ids = $request->user_ids;

        if (!Grade::query()->where('id',  $data['grade_id'])->exists()) {
            return response()->json(['message' => '班级不存在'], 403);
        }

        $user_ids = explode(',', $user_ids);

        $exitUids = GradeUser::query()->where('grade_id', $data['grade_id'])->pluck('user_id')->toArray();

        $createUids = array_values(array_diff($user_ids, $exitUids));

        $delUids = array_values(array_diff($exitUids, $user_ids));

        if (!empty($delUids)) {
            GradeUser::query()->where('grade_id', $data['grade_id'])->whereIn('user_id', $delUids)->delete();
        }

        if (empty($createUids)) {
            return response()->json(['message' => '这些用户已经在班级里了']);
        }

        $count = User::query()->whereIn('id', $createUids)->count();

        if (count($createUids) != $count) {
            return response()->json(['message' => '用户不存在'], 403);
        }

        $creates = [];
        foreach ($createUids as $uid) {
            $data['user_id'] = $uid;
            $creates[] = GradeUser::create($data);
        }

        // 触发自动分配作业
        if (!empty($createUids)) {
            $userHomeworks = UserHomework::query()->where('grade_id', $data['grade_id'])->get()->toArray();
            $homeworks = [];
            foreach ($userHomeworks as $userHomework) {
                if (isset($homeworks[$userHomework['homework_id']])) {
                    continue;
                }
                $homeworks[$userHomework['homework_id']] = $userHomework;
            }
            $userHomeworks = array_values($homeworks);
    
            foreach ($userHomeworks as $userHomework) {
                foreach ($createUids as $uid) {
                    $data = [
                        'user_id' => $uid,
                        'homework_id' => $userHomework['homework_id'],
                        'grade_id' => $userHomework['grade_id'],
                        'end_at' => $userHomework['end_at']
                    ];
                }
            }
        }

        return response()->json($creates);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '课程 id',
        ]);

        $id = $request->id;

        $gradeUser = GradeUser::query()->where('id', $id)->first();
        if (!$gradeUser) {
            return response()->json(['message' => '班级用户不存在'], 403);
        }

        $gradeUser->delete();

        return response()->json($gradeUser);
    }
}

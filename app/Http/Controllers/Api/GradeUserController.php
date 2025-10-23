<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GradeUser;
use App\Models\Grade;

class GradeUserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $gradeUsers = GradeUser::query()->where('user_id', $user->id)->with(['grade'])->get();

        return response()->json($gradeUsers);
    }

    public function grade(Request $request)
    {
        $request->validate([
            'grade_id' =>  'required|integer|min:1',
        ], [], [
            'grade_id' => '班级 id',
        ]);

        $user = $request->user();

        $grade = Grade::query()->where('id', $request->grade_id)->first();

        if (!$grade) {
            return response()->json(['message' => '班级不存在'], 403);
        }

        if ($user) {
            $grade['is_member'] = GradeUser::query()->where('grade_id', $grade->id)->whereIn('user_id', $user->id)->exists();
        } else {
            $grade['is_member'] = false;
        }
        return response()->json($grade);
    }

    public function store(Request $request)
    {
        $request->validate([
            'grade_id' =>  'required|integer|min:1',
        ], [], [
            'grade_id' => '班级 id',
        ]);

        $user = $request->user();

        $data = [
            'grade_id' => $request->grade_id,
            'user_id' => $user->id
        ];

        if (!Grade::query()->where('id',  $data['grade_id'])->exists()) {
            return response()->json(['message' => '班级不存在'], 403);
        }

        $gradeUser = GradeUser::query()->where('grade_id', $data['grade_id'])->whereIn('user_id', $data['user_id'])->first();
        if ($gradeUser) {
            return response()->json(['message' => '您已经在班级里了'], 403);
        }

        $gradeUser = GradeUser::create($data);

        return response()->json($gradeUser);
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

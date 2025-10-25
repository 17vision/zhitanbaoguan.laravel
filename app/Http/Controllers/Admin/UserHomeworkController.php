<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\GradeUser;
use App\Models\Homework;
use App\Models\UserHomework;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

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
            'grade_id' => 'required:user_id|integer|min:1',
            'end_at' => 'required|date',
        ], [], [
            'homework_id' => '作业 id',
            'grade_id' => '班级 id',
            'end_at' => '结束时间',
        ]);

        $grade_id = $request->grade_id;

        $data = $request->only(['homework_id', 'end_at']);

        if (!Carbon::now()->addHours(1)->lte($data['end_at'])) {
            return response()->json(['message' => '结束时间必须大于当前时间 1 小时'], 403);
        }

        if (!Homework::query()->where('id', $data['homework_id'])->exists()) {
            return response()->json(['message' => '作业不存在'], 403);
        }

        $gradeUids = GradeUser::query()->where('grade_id', $grade_id)->pluck('user_id')->toArray();

        $exitUids = UserHomework::query()->where('grade_id', $grade_id)->where('homework_id', $data['homework_id'])->pluck('user_id')->toArray();

        $delUids = array_diff($exitUids, $gradeUids);

        $createUids = array_diff($gradeUids, $exitUids);

        $updateUids = array_intersect($gradeUids, $exitUids);

        DB::beginTransaction();

        try {
            $updateNum = 0;
            $delNum = 0;
            $createNum = 0;

            if (!empty($updateUids)) {
                $updateNum = UserHomework::query()->where('grade_id', $grade_id)->where('homework_id', $data['homework_id'])->whereIn('user_id', $updateUids)->update([
                    'end_at' => $data['end_at']
                ]);
            }

            if (!empty($delUids)) {
                $delNum = UserHomework::query()->where('grade_id', $grade_id)->where('homework_id', $data['homework_id'])->whereIn('user_id', $delUids)->delete();
            }

            if (!empty($createUids)) {
                foreach ($createUids as $uid) {
                    $data['user_id'] = $uid;
                    $create[] = UserHomework::create($data);
                }
                $createNum = count($createUids);
            }
            DB::commit();
            return response()->json(['delNum' => $delNum, 'updateNum' => $updateNum, 'createNum' => $createNum]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('error')->error('分配作业失败', ['line' => $e->getLine(), 'message' => $e->getMessage(), 'trace' => $e->getTrace()]);
            return response()->json(['message' => '作业分配失败'], 403);
        }
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

<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Tutor;
use App\Models\CourseMessage;
use App\Models\CourseMessageReply;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
            'name' => 'filled|string',
            'status' => 'filled|in:0,1',
        ], [], [
            'page' => '当前页',
            'limit' => '单页显示条数',
            'name' => '名称',
            'status' => '状态',
        ]);

        $limit = $request->input('limit', 30);

        $title = $request->title;

        $query = Course::query()->with(['chapters.resource', 'tutor']);

        if (isset($request->status)) {
            $query->where('status', $request->status);
        }

        if ($title) {
            $query->where('title', 'like', '%' . $title . '%');
        }

        $courses = $query->paginate($limit);

        return response()->json($courses);
    }

    public function detail(Request $request, $id)
    {
        $role = Course::where('id', $id)->with(['chapters.resource', 'tutor'])->first();

        return response()->json($role);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|min:1|max:64',
            // 'duration' => 'required|integer',
            'category' => 'required|in:1,2,3,4,5',
            'difficulty' => 'required|in:1,2,3',
            'description' => 'filled|string|max:500',
            'cover' => 'filled|string',
            'tutor_id' =>  'filled|integer',
            'status' => 'filled|in:0,1',
        ], [], [
            'title' => '标题',
            // 'duration' => '时长',
            'category' => '分类',
            'difficulty' => '难度',
            'description' => '描述',
            'cover' => '封面',
            'tutor_id' => '导师 id',
            'status' => '状态',
        ]);

        $user = $request->user();

        $data = $request->only(['title', 'duration', 'category', 'difficulty', 'description', 'cover', 'tutor_id', 'status']);

        $data['user_id'] = $user->id;

        if (isset($data['cover']) && $data['cover']) {
            $data['cover'] = reverseStorageUrl($data['cover']);
        }

        if (isset($data['tutor_id']) && $data['tutor_id']) {
            if (!Tutor::query()->where('id',  $data['tutor_id'])->exists()) {
                return response()->json(['message' => '导师不存在'], 403);
            }
        }

        $course = Course::create($data);

        return response()->json($course);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'title' => 'filled|string|min:1|max:64',
            'duration' => 'filled|integer',
            'category' => 'filled|in:1,2,3,4,5',
            'difficulty' => 'filled|in:1,2,3',
            'description' => 'filled|string|max:500',
            'cover' => 'filled|string',
            'background' =>  'filled|string',
            'status' => 'filled|in:0,1',
        ], [], [
            'id' => '课程 id',
            'title' => '标题',
            'duration' => '时长',
            'category' => '分类',
            'difficulty' => '难度',
            'description' => '描述',
            'cover' => '封面',
            'background' => '背景',
            'status' => '状态',
        ]);

        $id = $request->id;

        $user = $request->user();

        $data = $request->only(['title', 'duration', 'category', 'difficulty', 'description', 'cover', 'tutor_id', 'status']);

        if (empty($data)) {
            return response()->json(['message' => '请提交有效数据'], 403);
        }

        $data['user_id'] = $user->id;

        if (isset($data['cover']) && $data['cover']) {
            $data['cover'] = reverseStorageUrl($data['cover']);
        }

        if (isset($data['tutor_id']) && $data['tutor_id']) {
            if (!Tutor::query()->where('id',  $data['tutor_id'])->exists()) {
                return response()->json(['message' => '导师不存在'], 403);
            }
        }

        $course = Course::query()->where('id', $id)->first();
        if (!$course) {
            return response()->json(['message' => '课程不存在'], 403);
        }

        $course->update($data);

        return response()->json($course);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '课程 id',
        ]);

        $id = $request->id;

        $course = Course::query()->where('id', $id)->first();
        if (!$course) {
            return response()->json(['message' => '课程不存在'], 403);
        }

        return response()->json($course);
    }

    public function getMessages(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|min:1',
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer|min:1',
        ], [], [
            'course_id' => '课程 id',
            'page' => '当前页',
            'limit' => '单页显示多少',
        ]);

        $limit = $request->input('limit', 20);

        $courseMessages = CourseMessage::query()->where('course_id', $request->course_id)->with(['user:id,nickname,gender,avatar', 'replies.user:id,nickname,gender,avatar'])->orderByDesc('id')->simplePaginate($limit);

        $mrids = [];

        $courseMessages->getCollection()->transform(function ($courseMessage) {
            $replies = [];
            foreach ($courseMessage['replies'] as $reply) {
                $replies[$reply['id']] = $reply;
            }

            foreach ($courseMessage['replies'] as &$reply) {
                if ($reply['course_message_reply_id']) {
                    $reply['reply_user'] = $replies[$reply['course_message_reply_id']]['user'] ?? null;
                }
            }
            return $courseMessage;
        });

        return response()->json($courseMessages);
    }

    public function deleteMessages(Request $request)
    {
        $request->validate([
            'course_message_id' => 'required_without:course_message_reply_id|integer|min:1',
            'course_message_reply_id' => 'required_without:course_message_id|integer|min:1',
        ], [], [
            'course_message_id' => '消息 id',
            'course_message_reply_id' => '二级消息 id'
        ]);

        $data = $request->only(['course_message_id', 'course_message_reply_id']);

        if (count($data) == 2) {
            return response()->json(['message' => '参数只能传一个'], 403);
        }

        if (isset($data['course_message_id'])) {
            if (!CourseMessage::query()->where('id', $data['course_message_id'])->exists()) {
                return response()->json(['message' => '消息不存在'], 403);
            }
        }

        if (isset($data['course_message_reply_id'])) {
            if (!CourseMessageReply::query()->where('id', $data['course_message_reply_id'])->exists()) {
                return response()->json(['message' => '二级消息不存在'], 403);
            }
        }

        DB::beginTransaction();
        try {
            $msg = '消息已删除';
            if (isset($data['course_message_id'])) {
                $courseMessage = CourseMessage::query()->where('id', $data['course_message_id'])->first();

                $msgNum = $courseMessage['reply_nums'] + 1;

                Course::query()->where('id', $courseMessage['course_id'])->where('message_count', '>=', $msgNum)->decrement('message_count', $msgNum);

                CourseMessageReply::query()->where('course_message_id', $data['course_message_id'])->delete();

                $courseMessage->delete();
            } else {

                $courseMessageReply = CourseMessageReply::query()->where('id', $data['course_message_reply_id'])->with(['courseMessage'])->first();

                CourseMessage::query()->where('id', $courseMessageReply['course_message_id'])->decrement('reply_nums', 1);

                CourseMessageReply::query()->where('course_message_reply_id', $data['course_message_reply_id'])->update(['course_message_reply_id' => null]);

                Course::query()->where('id', $courseMessageReply['courseMessage']['course_id'])->where('message_count', '>=', 1)->decrement('message_count');

                $courseMessageReply->delete();
            }

            DB::commit();

            return response()->json(['message' => $msg]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('error')->error('delete-message-error', ['message' => $e->getMessage(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => '操作失败'], 403);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CourseMessage;
use App\Models\CourseMessageReply;
use Exception;
use Illuminate\Http\Request;

class CourseMessageController extends Controller
{
    public function index(Request $request)
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

        $courseMessages = CourseMessage::query()->where('course_id', $request->course_id)->with(['user', 'replies.user'])->orderByDesc('id')->simplePaginate($limit);

        return response()->json($courseMessages);
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|min:1',
            'content' => 'required|string|max:1000',
            'type' => 'required|in:1,2,3'
        ], [], [
            'course_id' => '课程 id',
            'content' => '留言内容',
            'type' => '留言类型'
        ]);

        $user = $request()->user();

        $data = $request->only(['course_id', 'content', 'type']);

        $data['user_id'] = $user->id;

        if (in_array($data['type'], [2, 3])) {
            $data['content'] = reverseStorageUrl($data['content']);
        }

        if (!Course::query()->where('id', $data['course_id'])->exists()) {
            return response()->json(['message' => '课程不存在'], 403);
        }

        $courseMessage = CourseMessage::create($data);

        return response()->json($courseMessage);
    }

    public function reply(Request $request)
    {
        $request->validate([
            'course_message_id' => 'required|integer|min:1',
            'content' => 'required|string|max:1000',
            'type' => 'required|in:1,2,3',
            'course_message_reply_id' => 'filled|integer|min:1'
        ], [], [
            'course_message_id' => '消息 id',
            'content' => '留言内容',
            'type' => '留言类型',
            'course_message_reply_id' => '二级消息 id'
        ]);

        $user = $request()->user();

        $data = $request->only(['course_message_id', 'content', 'type', 'course_message_reply_id']);

        $data['user_id'] = $user->id;

        if (in_array($data['type'], [2, 3])) {
            $data['content'] = reverseStorageUrl($data['content']);
        }

        if (!CourseMessage::query()->where('id', $data['course_message_id'])->exists()) {
            return response()->json(['message' => '消息不存在'], 403);
        }

        if (isset($data['course_message_reply_id'])) {
            $courseMessageReply = CourseMessageReply::query()->where('id', $data['course_message_reply_id'])->first();

            if (!$courseMessageReply) {
                return response()->json(['message' => '二级消息不存在'], 403);
            }

            if ($courseMessageReply->course_message_id != $data['course_message_id']) {
                return response()->json(['message' => '二级消息必须同父级'], 403);
            }
        }

        DB::beginTransaction();
        try {
            $courseMessageReply = CourseMessageReply::create($data);

            CourseMessage::where('id', $data['course_message_id'])->increment('reply_nums', 1);

            DB::commit();

            return response()->json($courseMessageReply);
        } catch (Exception $e) {
            DB::rollBack();

            Log::channel('error')->error('reply-error', ['message' => $e->getMessage(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}

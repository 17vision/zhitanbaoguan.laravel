<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CourseMessage;
use App\Models\CourseMessageReply;
use App\Models\CourseMessagePraise;
use App\Models\CourseMessageReplyPraise;
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

        $courseMessages = CourseMessage::query()->where('course_id', $request->course_id)->with(['user:id,nickname,gender,avatar', 'replies.user:id,nickname,gender,avatar'])->orderByDesc('id')->simplePaginate($limit);

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

        $user = $request->user();

        $data = $request->only(['course_id', 'content', 'type']);

        $data['user_id'] = $user->id;

        if (in_array($data['type'], [2, 3])) {
            $data['content'] = reverseStorageUrl($data['content']);
        }

        if (!Course::query()->where('id', $data['course_id'])->exists()) {
            return response()->json(['message' => '课程不存在'], 403);
        }

        $courseMessage = CourseMessage::create($data);

        Course::query()->where('id', $data['course_id'])->increment('message_count', 1);

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

        $user = $request->user();

        $data = $request->only(['course_message_id', 'content', 'type', 'course_message_reply_id']);

        $data['user_id'] = $user->id;

        if (in_array($data['type'], [2, 3])) {
            $data['content'] = reverseStorageUrl($data['content']);
        }

        $courseMessage = CourseMessage::query()->where('id', $data['course_message_id'])->first();
        if (!$courseMessage) {
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

            $courseMessage->increment('reply_nums', 1);

            Course::query()->where('id', $courseMessage['course_id'])->increment('message_count', 1);

            DB::commit();

            return response()->json($courseMessageReply);
        } catch (Exception $e) {
            DB::rollBack();

            Log::channel('error')->error('reply-error', ['message' => $e->getMessage(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    // 对消息进行点赞
    public function praise(Request $request)
    {
        $request->validate([
            'course_message_id' => 'required_without:course_message_reply_id|integer|min:1',
            'course_message_reply_id' => 'required_without:course_message_id|integer|min:1',
        ], [], [
            'course_message_id' => '消息 id',
            'course_message_reply_id' => '二级消息 id'
        ]);

        $data = $request->only(['course_message_id', 'course_message_reply_id']);

        $user = $request->user();

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
            $msg = '';
            if (isset($data['course_message_id'])) {
                $coursePriase = CourseMessagePraise::query()->where('course_message_id', $data['course_message_id'])->where('user_id', $user->id)->first();
                if ($coursePriase) {
                    CourseMessage::query()->where('id', $data['course_message_id'])->decrement('praises_nums', 1);

                    $coursePriase->delete();

                    $msg = '已取消点赞';
                } else {
                    CourseMessage::query()->where('id', $data['course_message_id'])->increment('praises_nums', 1);

                    CourseMessagePraise::create([
                        'course_message_id' => $data['course_message_id'],
                        'user_id' => $user->id
                    ]);

                    $msg = '已点赞';
                }
            } else {
                $courseMessageReplyPraise = CourseMessageReplyPraise::query()->where('course_message_reply_id', $data['course_message_reply_id'])->where('user_id', $user->id)->first();
                if ($courseMessageReplyPraise) {
                    CourseMessageReply::query()->where('id', $data['course_message_reply_id'])->decrement('praises_nums', 1);

                    $courseMessageReplyPraise->delete();

                    $msg = '已取消点赞';
                } else {
                    $courseMessageReply = CourseMessageReply::query()->where('id', $data['course_message_reply_id'])->first();

                    CourseMessageReply::query()->where('id', $data['course_message_reply_id'])->increment('praises_nums', 1);

                    CourseMessageReplyPraise::create([
                        'course_message_id' => $courseMessageReply->course_message_id,
                        'course_message_reply_id' => $data['course_message_reply_id'],
                        'user_id' => $user->id
                    ]);

                    $msg = '已点赞';
                }
            }

            DB::commit();

            return response()->json(['message' => $msg]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('error')->error('reply-error', ['message' => $e->getMessage(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => '操作失败'], 403);
        }
    }
}

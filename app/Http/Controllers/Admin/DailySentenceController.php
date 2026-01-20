<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DailySentence;
use Carbon\Carbon;

class DailySentenceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
            'title' => 'filled|string',
        ], [], [
            'page' => '当前页',
            'limit' => '单页显示条数',
            'title' => '标题',
        ]);

        $limit = $request->input('limit', 30);

        $title = $request->input('title');

        $query = DailySentence::query();

        if ($title) {
            $query->where('title', 'like', "%$title%");
        }

        $dailySentences = $query->paginate($limit);

        return response()->json($dailySentences);
    }

    public function detail(Request $request, $id)
    {
        $dailySentence = DailySentence::where('id', $id)->first();

        return response()->json($dailySentence);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|min:1|max:64',
            'text' => 'required|string|max:255',
            'author' => 'filled|string|max:64',
            'image' => 'filled|string|max:255',
        ], [], [
            'title' => '标题',
            'text' => '文案',
            'author' => '作者',
            'image' => '图片',
        ]);

        $data = $request->only(['title', 'text', 'author', 'image']);

        $user = $request->user();

        $data['user_id'] = $user->id;

        $data['date'] = Carbon::now()->toDateString();

        if (isset($data['image']) && $data['image']) {
            $data['image'] = reverseStorageUrl($data['image']);
        }

        if (DailySentence::query()->where('date', $data['date'])->exists()) {
            return response()->json(['message' => '今天的每日一句已添加'], 403);
        }

        $dailySentence = DailySentence::create($data);

        return response()->json($dailySentence);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'title' => 'filled|string|min:1|max:64',
            'text' => 'filled|string|max:255',
            'author' => 'filled|string|max:64',
            'image' => 'filled|string|max:255',
        ], [], [
            'id' => '每日一句 id',
            'title' => '标题',
            'text' => '文案',
            'author' => '作者',
            'image' => '图片',
        ]);

        $id = $request->input('id');

        $user = $request->user();

        $data = $request->only(['title', 'text', 'author', 'image']);

        if (empty($data)) {
            return response()->json(['message' => '请提交数据'], 403);
        }

        $data['user_id'] = $user->id;

        if (isset($data['image']) && $data['image']) {
            $data['image'] = reverseStorageUrl($data['image']);
        }

        $dailySentence = DailySentence::query()->where('id', $id)->first();
        if (!$dailySentence) {
            return response()->json(['message' => '每日一句不存在'], 403);
        }

        $dailySentence->update($data);

        return response()->json($dailySentence);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '每日一句 id',
        ]);

        $id = $request->input('id');

        $dailySentence = DailySentence::query()->where('id', $id)->first();
        if (!$dailySentence) {
            return response()->json(['message' => '每日一句不存在'], 403);
        }

        return response()->json($dailySentence);
    }
}

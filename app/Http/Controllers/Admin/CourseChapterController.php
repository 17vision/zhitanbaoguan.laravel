<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseChapter;
use Illuminate\Http\Request;

class CourseChapterController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
            'name' => 'filled|string',
        ], [], [
            'page' => '当前页',
            'limit' => '单页显示条数',
            'name' => '名称',
        ]);

        $limit = $request->input('limit', 30);

        $title = $request->title;

        $query = CourseChapter::query();

        if ($title) {
            $query->where('title', 'like', '%' . $title . '%');
        }

        $courses = $query->paginate($limit);

        return response()->json($courses);
    }

    public function detail(Request $request, $id)
    {
        $role = CourseChapter::where('id', $id)->first();

        return response()->json($role);
    }

    public function store(Request $request) {
        // if (isset($data['background']) && $data['background']) {
        //     $data['background'] = reverseStorageUrl($data['background']);
        // }
    }
    public function update(Request $request) {}

    public function delete(Request $request) {}
}

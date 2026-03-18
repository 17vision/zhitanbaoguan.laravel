<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer|min:1|max:200',
            'list_status' => 'filled|in:1,2'
        ], [], [
            'page' => '页码',
            'limit' => '每页条数',
            'list_status' => '上架状态'
        ]);

        $limit = $request->input('limit', 20);

        $query = Workflow::query()->where('organization_id', 1)->where('status', 2);

        if ($request->has('list_status')) {
            $query->where('list_status', $request->input('list_status'));
        }

        $workflows = $query->paginate($limit);

        return response()->json($workflows);
    }

    public function show(Request $request, $id)
    {
        $workflow = Workflow::query()->where('id', $id)->first();

        return response()->json($workflow);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0.01|max:999999.99',
            'list_status' => 'filled|in:1,2'
        ], [], [
            'id' => '课程 id',
            'price' => '价格',
            'list_status' => '上架状态'
        ]);

        $id = $request->input('id');

        $data = $request->only(['price', 'list_status']);

        if (empty($data)) {
            return response()->json([
                'message' => '无需更新'
            ], 403);
        }

        $workflow = Workflow::query()->where('id', $id)->first();
        if (!$workflow) {
            return response()->json([
                'message' => '工作流不存在'
            ], 403);
        }
        
        $workflow->update($data);

        return response()->json([
            'message' => '更新成功'
        ]);
    }
}

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
            'limit' => 'filled|integer|min:1|max:200'
        ], [], [
            'page' => '页码',
            'limit' => '每页条数'
        ]);

        $limit = $request->input('limit', 20);

        $query = Workflow::query()->where('organization_id', 1)->where('status', 2);

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
            'id' => 'required|integer|min:1|exists:workflows,id',,
            'price' => 'nullable|numeric|min:0.01|max:999999.99',
        ], [], [
            'id' => '课程 id',
            'price' => '价格',
        ]);

        $id = $request->input('id');

        $data = $request->only(['price']);

        if (empty($data)) {
            return response()->json([
                'message' => '无需更新'
            ], 403);
        }

        Workflow::query()->where('id', $id)->update($data);

        return response()->json([
            'message' => '更新成功'
        ]);
    }
}

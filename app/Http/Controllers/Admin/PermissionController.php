<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Role;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'parent_id' => 'filled|integer',
            'limit' => 'filled|integer'
        ], [], [
            'parent_id' => '父 id',
            'limit' => '分页'
        ]);

        $limit = $request->input('limit', 30);

        $parent_id = $request->parent_id ?? 0;

        $permissions = Permission::where('parent_id', $parent_id)->with('childs')->orderBy('order', 'ASC')->paginate($limit);

        return response()->json($permissions);
    }

    public function detail(Request $request, $id)
    {
        $permission = Permission::where('id', $id)->first();

        return response()->json($permission);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:16',
            'name' => [
                'required',
                'regex:/^([a-z][a-z0-9]*[.]*[a-z][a-z0-9]*)|([a-z][a-z0-9]*)$/'
            ],
            'path' => [
                'required',
                'regex:/^(\/[a-z]|[a-z])[a-z0-9]*$/'
            ],
            'icon' => 'nullable|max:16',
            'hidden' => 'required|in:0,1',
            'keep_alive' => 'required|in:0,1',
            'always_show' => 'required|in:0,1',
            'component' => 'nullable|max:64',
            'link' => 'nullable|url',
            'iframe' => 'nullable|url',
            'redirect' => 'nullable|max:128',
            'parent_id' => 'nullable|integer',
            'id' => 'filled|integer',
        ],[],[
            'title' => '菜单名称',
            'name' => '名称',
            'path' =>'路由',
            'icon' => 'icon',
            'hidden' => '隐藏',
            'keep_alive' => 'keep alive',
            'always_show' => '显示父级',
            'component' => '组件',
            'link' => '外链',
            'iframe' => 'Iframe',
            'redirect' => '重定向',
            'id' => '权限 id',
        ]);

        $data = $request->only(['title', 'name', 'path', 'icon', 'hidden', 'keep_alive', 'always_show', 'component', 'link', 'iframe', 'redirect', 'parent_id']);

        $id = $request->id;

        if (isset($data['parent_id']) && $data['parent_id']) {
            if (!Permission::where('id', $data['parent_id'])->exists()) {
                return response()->json(['message' => '父权限不存在'], 403);
            }
        }

        if ($id) {
            $permission = Permission::query()->where('id', $id)->update($data);
        } else {
            $permission = Permission::create($data);

            // 根角色，默认都会加上权限
            $rootRole = Role::query()->where('name', 'root')->first();
            if ($rootRole) {
                $rootRole->givePermissionTo($permission);
            }
        }
        return response()->json($permission);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => []
        ],[],[
            'id' => '权限 id'
        ]);

        $id = $request->id;

        $permission = Permission::query()->where('id', $id)->first();

        if ($permission['path'] === '/') {
            return response()->json(['message' => '根权限不能被删'], 403);
        }

        // 权限以及子权限都要删除
        $permissions = Permission::query()->where('id', $id)->with(['childs'])->get();

        $ids = [];
        function getIds($ps, &$ids) {
            foreach($ps as $p) {
                array_push($ids, $p['id']);

                if (!empty($p['childs'])) {
                    getIds($p['childs'], $ids);
                }
            }
        }
        getIds($permissions, $ids);

        $deletes = Permission::query()->whereIn('id', $ids)->delete();

        return response()->json(['deletes' => $deletes]);
    }

    // 更新顺序
    public function updateOrders(Request $request)
    {
        $request->validate([
            'ids' => []
        ],[],[
            'ids' => '权限 id'
        ]);

        $ids = $request->ids;

        // $rootPermission = Permission::query()->where('path', '/')->whereIn('id', $ids)->first();
        // if ($rootPermission && $ids[0] != $rootPermission['id']) {
        //     return response()->json(['message' => '根目录必须排在第一位'], 403);
        // }

        // $data = [];
        // foreach($ids as  $index => $id) {
        //     $data[] = [
        //         'id' => $id,
        //         'order' => $index + 1
        //     ];
        // }
        // // $result = Permission::query()->upsert($data, ['id'], ['order']);

        $data = [];
        foreach($ids as  $index => $id) {
            $result = Permission::query()->where('id', $id)->update(['order' => $index + 1]);

            $data[] = $result;
        }

        return response()->json($data);
    }
}


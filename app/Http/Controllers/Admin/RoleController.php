<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'limit' => 'filled|integer'
        ], [], [
            'limit' => '分页'
        ]);

        $limit = $request->input('limit', 30);

        $roles = Role::paginate($limit);

        return response()->json($roles);
    }

    public function detail(Request $request, $id)
    {
        $role = Role::where('id', $id)->first();

        return response()->json($role);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:16',
            'name' => [
                'required',
                'regex:/^([a-z][a-z0-9]*[.]*[a-z][a-z0-9]*)|([a-z][a-z0-9]*)$/'
            ],
            'id' => 'filled|integer',
        ],[],[
            'title' => '显示名称',
            'name' => '角色名称',
            'id' => '角色 id',
        ]);

        $data = $request->only(['title', 'name']);

        $id = $request->id;

        if ($id) {
            $role = Role::where('id', $id)->update($data);
        } else {
            $role = Role::create($data);
        }

        return response()->json($role);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ],[],[
            'id' => '权限 id'
        ]);

        $id = $request->id;

        $delete = Role::where('id', $id)->delete();

        return response()->json(['delete' => $delete]);
    }

    public function getPermissions(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ], [], [
            'id' => '角色 id'
        ]);

        $id = $request->id;

        $role = Role::findOrFail($id);
        if (!$role) {
            return response()->json(['message' => '当前角色不存在', 403]);
        }

        $checkedIds = [];
        $permissions = $role->permissions()->get();
        foreach($permissions as $permission) {
            $checkedIds[] = $permission['id'];
        }

        $permissions = Permission::query()->where('parent_id', 0)->with(['childs'])->get();

        return response()->json(['role' => $role->title, 'permissions' => $permissions, 'checkedIds' => $checkedIds]);
    }

    public function setPermission(Request $request)
    {
        $request->validate([
            'role_id' => 'required|integer',
            'ids' => 'required|array'
        ], [], [
            'role_id' => '角色 id',
            'ids' => '权限 id'
        ]);

        $role_id = $request->input('role_id');
        $ids = $request->input('ids');
        $role = Role::query()->findOrFail($role_id);
        if (empty($ids)) {
            $result = $role->permissions()->detach();
        } else {
            $result = $role->syncPermissions($ids);
        }
        return response()->json($result);
    }
}

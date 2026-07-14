<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VipOrder;
use App\Models\VipPackage;
use Illuminate\Http\Request;

class VipPackageController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'filled|integer|min:1',
            'limit' => 'filled|integer',
            'venue_id' => 'required|integer|exists:venues,id',
        ], [], [
            'page' => '当前页',
            'limit' => '单页显示条数',
            'venue_id' => '场馆 id',
        ]);

        $limit = $request->input('limit', 30);

        $venue_id = $request->input('venue_id');

        $packages = VipPackage::query()
            ->where('venue_id', $venue_id)
            ->orderBy('sort')
            ->orderByDesc('id')
            ->paginate($limit);

        return response()->json($packages);
    }

    public function detail(Request $request, $id)
    {
        $package = VipPackage::query()->where('id', $id)->first();

        if (!$package) {
            return response()->json(['message' => '套餐不存在'], 403);
        }

        return response()->json($package);
    }

    public function store(Request $request)
    {
        $request->validate([
            'venue_id' => 'required|integer|exists:venues,id',
            'package_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'original_price' => 'required|numeric|min:0',
            'is_recommend' => 'filled|in:0,1',
            'is_only_once' => 'filled|in:0,1',
            'combine_count' => 'filled|integer|min:0',
            'chinese_explain' => 'filled|in:0,1',
            'multi_explain' => 'filled|in:0,1',
            'sort' => 'filled|integer',
            'status' => 'filled|in:0,1,2',
        ], [], [
            'venue_id' => '场馆 id',
            'package_name' => '套餐名称',
            'description' => '套餐描述',
            'price' => '实际售价',
            'original_price' => '原价',
            'is_recommend' => '是否推荐',
            'is_only_once' => '是否只能购买一次',
            'combine_count' => '合成照片次数',
            'chinese_explain' => '中文讲解',
            'multi_explain' => '多语言讲解',
            'sort' => '排序权重',
            'status' => '套餐状态',
        ]);

        $data = $request->only([
            'venue_id',
            'package_name',
            'description',
            'price',
            'original_price',
            'is_recommend',
            'is_only_once',
            'combine_count',
            'chinese_explain',
            'multi_explain',
            'sort',
            'status',
        ]);

        $venue = Venue::query()->where('id', $data['venue_id'])->first();

        $data['organization_id'] = $venue['organization_id'];

        $package = VipPackage::create($data);

        return response()->json($package);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:vip_packages,id',
            'package_name' => 'filled|string|max:100',
            'description' => 'nullable|string',
            'price' => 'filled|numeric|min:0',
            'original_price' => 'filled|numeric|min:0',
            'is_recommend' => 'filled|in:0,1',
            'is_only_once' => 'filled|in:0,1',
            'combine_count' => 'filled|integer|min:0',
            'chinese_explain' => 'filled|in:0,1',
            'multi_explain' => 'filled|in:0,1',
            'sort' => 'filled|integer',
            'status' => 'filled|in:0,1,2',
        ], [], [
            'id' => '套餐 id',
            'package_name' => '套餐名称',
            'description' => '套餐描述',
            'price' => '实际售价',
            'original_price' => '原价',
            'is_recommend' => '是否推荐',
            'is_only_once' => '是否只能购买一次',
            'combine_count' => '合成照片次数',
            'chinese_explain' => '中文讲解',
            'multi_explain' => '多语言讲解',
            'sort' => '排序权重',
            'status' => '套餐状态',
        ]);

        $data = $request->only([
            'package_name',
            'description',
            'price',
            'original_price',
            'is_recommend',
            'is_only_once',
            'combine_count',
            'chinese_explain',
            'multi_explain',
            'sort',
            'status',
        ]);

        if (empty($data)) {
            return response()->json(['message' => '请输入要更新的内容'], 403);
        }

        $package = VipPackage::query()->where('id', $request->id)->first();

        $package->update($data);

        return response()->json($package);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '套餐 id',
        ]);

        $id = $request->id;

        $package = VipPackage::query()->where('id', $id)->first();

        if (!$package) {
            return response()->json(['message' => '套餐不存在'], 403);
        }

        if (VipOrder::query()->where('vip_package_id', $id)->exists()) {
            return response()->json(['message' => '已有套餐购买记录，无法删除套餐'], 403);
        }

        $delete = $package->delete();

        return response()->json(['delete' => $delete]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardIndexRequest;
use App\Http\Requests\ShippingZone\StoreShippingZoneRequest;
use App\Http\Requests\ShippingZone\UpdateShippingZoneRequest;
use App\Http\Resources\ShippingZoneResource;
use App\Models\ShippingZone;
use Illuminate\Http\JsonResponse;

class ShippingZoneController extends Controller
{
    public function publicIndex(): JsonResponse
    {
        $zones = ShippingZone::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ShippingZoneResource::collection($zones),
            'message' => 'Lấy danh sách khu vực giao hàng thành công.',
        ]);
    }

    public function index(DashboardIndexRequest $request): JsonResponse
    {
        $zones = ShippingZone::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();

                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'items' => ShippingZoneResource::collection($zones->getCollection()),
                'pagination' => [
                    'current_page' => $zones->currentPage(),
                    'per_page' => $zones->perPage(),
                    'total' => $zones->total(),
                    'last_page' => $zones->lastPage(),
                ],
            ],
            'message' => 'Lấy danh sách khu vực giao hàng quản trị thành công.',
        ]);
    }

    public function store(StoreShippingZoneRequest $request): JsonResponse
    {
        $zone = ShippingZone::query()->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => new ShippingZoneResource($zone),
            'message' => 'Tạo khu vực giao hàng thành công.',
        ], 201);
    }

    public function update(UpdateShippingZoneRequest $request, ShippingZone $shippingZone): JsonResponse
    {
        $shippingZone->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => new ShippingZoneResource($shippingZone->refresh()),
            'message' => 'Cập nhật khu vực giao hàng thành công.',
        ]);
    }

    public function toggleActive(ShippingZone $shippingZone): JsonResponse
    {
        $shippingZone->update(['is_active' => ! $shippingZone->is_active]);

        return response()->json([
            'success' => true,
            'data' => new ShippingZoneResource($shippingZone->refresh()),
            'message' => 'Cập nhật trạng thái khu vực giao hàng thành công.',
        ]);
    }

    public function destroy(ShippingZone $shippingZone): JsonResponse
    {
        $shippingZone->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Xóa khu vực giao hàng thành công.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardIndexRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;

class MyOrderController extends Controller
{
    public function index(DashboardIndexRequest $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with(['user.role', 'items.course.category', 'items.course.instructor.role'])
            ->when($request->filled('status'), fn ($query) => $query->where(
                'status',
                $request->string('status')->toString(),
            ))
            ->latest('id')
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => [
                'items' => OrderResource::collection($orders->getCollection()),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ],
            ],
            'message' => 'Lấy lịch sử đơn hàng thành công.',
        ]);
    }
}

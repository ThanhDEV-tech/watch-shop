<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardIndexRequest;
use App\Http\Resources\AdminOrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\StockMovementResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\VnpayTransactionResource;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\VnpayTransaction;
use App\Services\AdminDashboardService;
use App\Services\VnpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboardService,
        private readonly VnpayService $vnpayService,
    ) {}

    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->stats(),
            'message' => 'Lấy thống kê quản trị thành công.',
        ]);
    }

    public function users(DashboardIndexRequest $request): JsonResponse
    {
        $users = User::query()
            ->with('role')
            ->when($request->filled('role'), fn ($query) => $query->whereHas(
                'role',
                fn ($roleQuery) => $roleQuery->where('name', $request->string('role')->toString()),
            ))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->where(fn ($searchQuery) => $searchQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $this->paginatedData($users, UserResource::collection($users->getCollection())),
            'message' => 'Lấy danh sách người dùng thành công.',
        ]);
    }

    public function toggleActive(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Bạn không thể tự khóa tài khoản của mình.',
            ], 403);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->load('role')),
            'message' => $user->is_active ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.',
        ]);
    }

    public function orders(DashboardIndexRequest $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['user.role', 'items.product', 'items.productVariant'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $this->paginatedData($orders, OrderResource::collection($orders->getCollection())),
            'message' => 'Lấy danh sách đơn hàng thành công.',
        ]);
    }

    public function order(Order $order): JsonResponse
    {
        $order->load([
            'user',
            'items.product',
            'items.productVariant',
            'vnpayTransactions' => fn ($query) => $query->latest('id')->limit(1),
        ]);

        return response()->json([
            'success' => true,
            'data' => new AdminOrderDetailResource($order),
            'message' => 'Lấy chi tiết đơn hàng thành công.',
        ]);
    }

    public function markOrderAsPaid(Request $request, Order $order): JsonResponse
    {
        $admin = $request->user();

        $processedOrder = DB::transaction(function () use ($admin, $order): Order|JsonResponse {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Chỉ có thể đánh dấu đã thanh toán cho đơn hàng đang pending.',
                ], 400);
            }

            $params = [
                'vnp_TxnRef' => $lockedOrder->code,
                'vnp_Amount' => (string) ((int) round((float) $lockedOrder->total_amount * 100)),
                'vnp_ResponseCode' => '00',
                'vnp_TransactionStatus' => 'MANUAL_ADMIN',
                'vnp_BankCode' => 'MANUAL_ADMIN',
                'vnp_TransactionNo' => null,
                'manual_admin_id' => $admin->id,
                'manual_reason' => 'Marked as paid from admin dashboard',
            ];

            $this->vnpayService->processSuccessfulPayment($lockedOrder, $params, $admin->id);

            Log::info("Order {$lockedOrder->code} manually marked as paid by admin {$admin->id}");

            return $lockedOrder->refresh();
        });

        if ($processedOrder instanceof JsonResponse) {
            return $processedOrder;
        }

        $processedOrder->load([
            'user',
            'items.product',
            'items.productVariant',
            'vnpayTransactions' => fn ($query) => $query->latest('id')->limit(1),
        ]);

        return response()->json([
            'success' => true,
            'data' => new AdminOrderDetailResource($processedOrder),
            'message' => 'Đã đánh dấu đơn hàng là đã thanh toán.',
        ]);
    }

    public function markOrderAsRefunded(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'refund_note' => ['nullable', 'string'],
        ]);

        $processedOrder = DB::transaction(function () use ($order, $validated): Order|JsonResponse {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedOrder->status, ['paid', 'paid_stock_issue', 'shipping'], true)) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Chỉ có thể đánh dấu hoàn tiền cho đơn đã thanh toán, lỗi tồn kho hoặc đang giao.',
                ], 400);
            }

            $lockedOrder->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refund_note' => $validated['refund_note'] ?? null,
            ]);

            return $lockedOrder->refresh();
        });

        if ($processedOrder instanceof JsonResponse) {
            return $processedOrder;
        }

        $processedOrder->load([
            'user',
            'items.product',
            'items.productVariant',
            'vnpayTransactions' => fn ($query) => $query->latest('id')->limit(1),
        ]);

        return response()->json([
            'success' => true,
            'data' => new AdminOrderDetailResource($processedOrder),
            'message' => 'Đã đánh dấu đơn hàng là đã hoàn tiền.',
        ]);
    }

    public function vnpayTransactions(DashboardIndexRequest $request): JsonResponse
    {
        $transactions = VnpayTransaction::query()
            ->with(['order.user.role', 'order.items.product', 'order.items.productVariant'])
            ->when($request->filled('response_code'), fn ($query) => $query->where(
                'response_code',
                $request->string('response_code')->toString(),
            ))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $this->paginatedData(
                $transactions,
                VnpayTransactionResource::collection($transactions->getCollection()),
            ),
            'message' => 'Lấy danh sách giao dịch VNPay thành công.',
        ]);
    }

    public function stockMovements(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $movements = StockMovement::query()
            ->with(['productVariant.product.brand', 'order.user.role', 'creator.role'])
            ->when(isset($validated['product_variant_id']), fn ($query) => $query->where(
                'product_variant_id',
                $validated['product_variant_id'],
            ))
            ->when(isset($validated['order_id']), fn ($query) => $query->where('order_id', $validated['order_id']))
            ->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 15));

        return response()->json([
            'success' => true,
            'data' => $this->paginatedData(
                $movements,
                StockMovementResource::collection($movements->getCollection()),
            ),
            'message' => 'Lấy lịch sử tồn kho thành công.',
        ]);
    }

    /** @return array<string, mixed> */
    private function paginatedData(LengthAwarePaginator $paginator, mixed $items): array
    {
        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}

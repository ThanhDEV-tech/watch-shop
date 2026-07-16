<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\VnpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VnpayController extends Controller
{
    public function __construct(private readonly VnpayService $vnpayService) {}

    public function createPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['nullable', 'integer'],
        ]);

        $orderQuery = $request->user()
            ->orders()
            ->where('status', 'pending');

        if (isset($validated['order_id'])) {
            $orderQuery->whereKey($validated['order_id']);
        }

        $order = $orderQuery->latest()->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Không tìm thấy đơn hàng đang chờ thanh toán.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'payment_url' => $this->vnpayService->buildPaymentUrl($order, $request),
            ],
            'message' => 'Tạo URL thanh toán VNPay thành công.',
        ]);
    }

    public function handleReturn(Request $request): JsonResponse
    {
        $params = $request->query();

        if (! $this->vnpayService->verifySecureHash($params)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Chữ ký không hợp lệ.',
            ], 400);
        }

        $responseCode = (string) ($params['vnp_ResponseCode'] ?? '');
        $isSuccess = $responseCode === '00';
        $processingStatus = 'display_only';
        $message = $isSuccess
            ? 'Thanh toán thành công.'
            : 'Thanh toán không thành công.';

        if ($isSuccess) {
            $processingResult = DB::transaction(function () use ($params): array {
                $order = Order::query()
                    ->where('code', (string) ($params['vnp_TxnRef'] ?? ''))
                    ->lockForUpdate()
                    ->first();

                if (! $order) {
                    return [
                        'is_success' => false,
                        'message' => 'Không tìm thấy đơn hàng.',
                        'processing_status' => 'order_not_found',
                    ];
                }

                if (! $this->vnpayService->amountMatches($order, $params)) {
                    return [
                        'is_success' => false,
                        'message' => 'Số tiền thanh toán không khớp với đơn hàng.',
                        'processing_status' => 'invalid_amount',
                    ];
                }

                if ($order->status !== 'pending') {
                    if ($order->status === 'paid') {
                        $this->vnpayService->clearPurchasedCartItems($order);
                    }

                    return [
                        'is_success' => $order->status === 'paid',
                        'message' => $order->status === 'paid'
                            ? 'Thanh toán thành công.'
                            : 'Đơn hàng đã được xử lý trước đó.',
                        'processing_status' => 'already_processed',
                    ];
                }

                $this->vnpayService->processSuccessfulPayment($order, $params);

                return [
                    'is_success' => true,
                    'message' => 'Thanh toán thành công.',
                    'processing_status' => 'processed',
                ];
            });

            $isSuccess = $processingResult['is_success'];
            $message = $processingResult['message'];
            $processingStatus = $processingResult['processing_status'];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'response_code' => $responseCode,
                'order_code' => (string) ($params['vnp_TxnRef'] ?? ''),
                'amount' => ((float) ($params['vnp_Amount'] ?? 0)) / 100,
                'is_success' => $isSuccess,
                'processing_status' => $processingStatus,
            ],
            'message' => $message,
        ]);
    }

    public function handleIpn(Request $request): JsonResponse
    {
        $params = $request->all();

        if (! $this->vnpayService->verifySecureHash($params)) {
            return $this->ipnResponse('97', 'Invalid signature');
        }

        return DB::transaction(function () use ($params): JsonResponse {
            $order = Order::query()
                ->where('code', (string) ($params['vnp_TxnRef'] ?? ''))
                ->lockForUpdate()
                ->first();

            if (! $order) {
                return $this->ipnResponse('01', 'Order not found');
            }

            if (! $this->vnpayService->amountMatches($order, $params)) {
                return $this->ipnResponse('04', 'Invalid amount');
            }

            if ($order->status !== 'pending') {
                return $this->ipnResponse('02', 'Order already confirmed');
            }

            if ((string) ($params['vnp_ResponseCode'] ?? '') === '00') {
                $this->vnpayService->processSuccessfulPayment($order, $params);
            } else {
                $this->vnpayService->processFailedPayment($order, $params);
            }

            return $this->ipnResponse('00', 'Confirm Success');
        });
    }

    private function ipnResponse(string $code, string $message): JsonResponse
    {
        return response()->json([
            'RspCode' => $code,
            'Message' => $message,
        ]);
    }
}

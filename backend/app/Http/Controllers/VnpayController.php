<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\VnpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

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

        try {
            $paymentUrl = $this->vnpayService->buildPaymentUrl($order, $request);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Cấu hình VNPay chưa đầy đủ. Vui lòng kiểm tra VNPAY_TMN_CODE, VNPAY_HASH_SECRET, VNPAY_URL, VNPAY_RETURN_URL.',
            ], 500);
        }

        Log::info('VNPay payment URL generated', [
            'order_id' => $order->id,
            'order_code' => $order->code,
            'payment_url' => $paymentUrl,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'payment_url' => $paymentUrl,
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
        $order = Order::query()->where('code', (string) ($params['vnp_TxnRef'] ?? ''))->first();

        return response()->json([
            'success' => true,
            'data' => [
                'response_code' => $responseCode,
                'order_code' => (string) ($params['vnp_TxnRef'] ?? ''),
                'amount' => ((float) ($params['vnp_Amount'] ?? 0)) / 100,
                'is_success' => $isSuccess,
                'processing_status' => 'display_only',
                'order_status' => $order?->status,
                'amount_matches' => $order ? $this->vnpayService->amountMatches($order, $params) : null,
            ],
            'message' => $isSuccess
                ? 'Thanh toán thành công. Hệ thống đang chờ IPN xác nhận đơn hàng.'
                : 'Thanh toán không thành công.',
        ]);
    }

    public function handleIpn(Request $request): JsonResponse
    {
        $params = $request->all();

        if (! $this->vnpayService->verifySecureHash($params)) {
            return $this->ipnResponse('97', 'Invalid signature');
        }

        $order = Order::query()
            ->where('code', (string) ($params['vnp_TxnRef'] ?? ''))
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
    }

    private function ipnResponse(string $code, string $message): JsonResponse
    {
        return response()->json([
            'RspCode' => $code,
            'Message' => $message,
        ]);
    }
}

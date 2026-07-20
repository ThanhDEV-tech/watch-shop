<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\VnpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

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
        $orderCode = (string) ($params['vnp_TxnRef'] ?? '');
        $order = Order::query()->where('code', $orderCode)->first();
        $queryDrResult = null;
        $processingStatus = 'display_only';

        if ($order && in_array($order->status, ['paid', 'paid_stock_issue'], true)) {
            $processingStatus = 'already_processed';
        } elseif ($order) {
            try {
                $queryDrResult = $this->vnpayService->queryTransactionStatus($order, $request->ip());

                if ($queryDrResult['payment_success'] && $this->vnpayService->amountMatches($order, $queryDrResult['response'])) {
                    $this->vnpayService->processSuccessfulPayment($order, $queryDrResult['response']);
                    $order->refresh();
                    $processingStatus = 'querydr_paid';
                } elseif ($queryDrResult['payment_success']) {
                    $processingStatus = 'querydr_amount_mismatch';

                    Log::warning('VNPay QueryDR amount mismatch', [
                        'order_id' => $order->id,
                        'order_code' => $order->code,
                        'expected_amount' => (int) round((float) $order->total_amount * 100),
                        'received_amount' => (int) ($queryDrResult['response']['vnp_Amount'] ?? 0),
                    ]);
                } else {
                    $processingStatus = 'querydr_not_paid';
                }
            } catch (Throwable $exception) {
                report($exception);
                $processingStatus = 'querydr_error';

                Log::error('VNPay QueryDR failed during return handling', [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'response_code' => $responseCode,
                'order_code' => $orderCode,
                'amount' => ((float) ($params['vnp_Amount'] ?? 0)) / 100,
                'is_success' => $isSuccess,
                'processing_status' => $processingStatus,
                'order_status' => $order?->status,
                'amount_matches' => $order ? $this->vnpayService->amountMatches($order, $params) : null,
                'querydr' => $queryDrResult ? [
                    'ok' => $queryDrResult['ok'],
                    'payment_success' => $queryDrResult['payment_success'],
                    'signature_verified' => $queryDrResult['signature_verified'],
                    'response_code' => (string) ($queryDrResult['response']['vnp_ResponseCode'] ?? ''),
                    'transaction_status' => (string) ($queryDrResult['response']['vnp_TransactionStatus'] ?? ''),
                    'transaction_no' => (string) ($queryDrResult['response']['vnp_TransactionNo'] ?? ''),
                    'message' => $queryDrResult['message'],
                ] : null,
            ],
            'message' => $isSuccess
                ? 'Thanh toán thành công. Hệ thống đã xác thực giao dịch với VNPay.'
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

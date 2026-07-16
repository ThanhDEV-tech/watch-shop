<?php

namespace App\Services;

use App\Mail\OrderPaidMail;
use App\Models\Cart;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\VnpayTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class VnpayService
{
    private readonly string $tmnCode;

    private readonly string $hashSecret;

    private readonly string $url;

    private readonly string $returnUrl;

    private readonly string $ipnUrl;

    public function __construct()
    {
        $this->tmnCode = (string) config('services.vnpay.tmn_code');
        $this->hashSecret = (string) config('services.vnpay.hash_secret');
        $this->url = (string) config('services.vnpay.url');
        $this->returnUrl = (string) config('services.vnpay.return_url');
        $this->ipnUrl = (string) config('services.vnpay.ipn_url');
    }

    public function buildPaymentUrl(Order $order, Request $request): string
    {
        $paymentTime = now('Asia/Ho_Chi_Minh');
        $ip = $request->ip();

        if ($ip === '::1' || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = '127.0.0.1';
        }

        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_Amount' => (string) ((int) round((float) $order->total_amount * 100)),
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $order->code,
            'vnp_OrderInfo' => 'Thanh toan don hang '.$order->code,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => $this->returnUrl,
            'vnp_IpAddr' => $ip,
            'vnp_CreateDate' => $paymentTime->format('YmdHis'),
            'vnp_ExpireDate' => $paymentTime->copy()->addMinutes(15)->format('YmdHis'),
        ];

        $hashData = $this->buildHashData($params);
        $signedParams = $this->signParams($params);

        return $this->url.'?'.$hashData.'&vnp_SecureHash='.$signedParams['vnp_SecureHash'];
    }

    /** @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function signParams(array $params): array
    {
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);

        $params['vnp_SecureHash'] = hash_hmac(
            'sha512',
            $this->buildHashData($params),
            $this->hashSecret,
        );

        return $params;
    }

    /** @param array<string, mixed> $params */
    public function verifySecureHash(array $params): bool
    {
        $secureHash = (string) ($params['vnp_SecureHash'] ?? '');

        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);

        if ($secureHash === '') {
            return false;
        }

        $calculatedHash = hash_hmac('sha512', $this->buildHashData($params), $this->hashSecret);

        return hash_equals($calculatedHash, $secureHash);
    }

    /** @param array<string, mixed> $params */
    public function amountMatches(Order $order, array $params): bool
    {
        return $this->expectedAmount($order) === $this->receivedAmount($params);
    }

    /** @param array<string, mixed> $params */
    public function processSuccessfulPayment(Order $order, array $params, ?int $adminId = null): void
    {
        $receivedAmount = $this->receivedAmount($params);

        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->createTransaction($order, $params, $receivedAmount, $adminId);

        $order->loadMissing('items');

        foreach ($order->items as $item) {
            Enrollment::query()->firstOrCreate(
                [
                    'user_id' => $order->user_id,
                    'course_id' => $item->course_id,
                ],
                [
                    'order_id' => $order->id,
                    'enrolled_at' => now(),
                ],
            );
        }

        $this->clearPurchasedCartItems($order);

        $order->loadMissing(['user', 'items.course']);

        DB::afterCommit(function () use ($order): void {
            Mail::to($order->user->email)->queue(new OrderPaidMail($order));
        });
    }

    public function clearPurchasedCartItems(Order $order): void
    {
        $order->loadMissing('items');
        $purchasedCourseIds = $order->items->pluck('course_id')->filter()->values();

        if ($purchasedCourseIds->isEmpty()) {
            return;
        }

        $cart = Cart::query()
            ->where('user_id', $order->user_id)
            ->lockForUpdate()
            ->first();

        $cart?->items()
            ->whereIn('course_id', $purchasedCourseIds)
            ->delete();
    }

    /** @param array<string, mixed> $params */
    public function processFailedPayment(Order $order, array $params): void
    {
        $receivedAmount = $this->receivedAmount($params);

        $order->update([
            'status' => 'failed',
            'paid_at' => null,
        ]);

        $this->createTransaction($order, $params, $receivedAmount);
    }

    /** @param array<string, mixed> $params */
    private function buildHashData(array $params): string
    {
        ksort($params);

        return implode('&', array_map(
            fn (string $key, mixed $value): string => urlencode($key).'='.urlencode((string) $value),
            array_keys($params),
            array_values($params),
        ));
    }

    private function expectedAmount(Order $order): int
    {
        return (int) round((float) $order->total_amount * 100);
    }

    /** @param array<string, mixed> $params */
    private function receivedAmount(array $params): int
    {
        return (int) ($params['vnp_Amount'] ?? 0);
    }

    /** @param array<string, mixed> $params */
    private function createTransaction(Order $order, array $params, int $receivedAmount, ?int $adminId = null): void
    {
        VnpayTransaction::query()->create([
            'order_id' => $order->id,
            'admin_id' => $adminId,
            'txn_ref' => (string) ($params['vnp_TxnRef'] ?? ''),
            'amount' => $receivedAmount / 100,
            'bank_code' => $params['vnp_BankCode'] ?? null,
            'transaction_no' => $params['vnp_TransactionNo'] ?? null,
            'response_code' => $params['vnp_ResponseCode'] ?? null,
            'transaction_status' => $params['vnp_TransactionStatus'] ?? null,
            'secure_hash' => $params['vnp_SecureHash'] ?? null,
            'raw_response' => $params,
            'is_verified' => true,
        ]);
    }
}

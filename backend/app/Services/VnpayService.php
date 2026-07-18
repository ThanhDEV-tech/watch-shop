<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\VnpayTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

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
        $this->ensurePaymentConfig();

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
        $paymentUrl = $this->url.'?'.$hashData.'&vnp_SecureHash='.$signedParams['vnp_SecureHash'];

        Log::info('VNPay payment signing generated', [
            'logged_at' => now('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s.vP'),
            'order_id' => $order->id,
            'order_code' => $order->code,
            'hashdata' => $hashData,
            'hash_secret_length' => strlen($this->hashSecret),
            'secure_hash' => $signedParams['vnp_SecureHash'],
            'payment_url' => $paymentUrl,
        ]);

        return $paymentUrl;
    }

    private function ensurePaymentConfig(): void
    {
        $missing = collect([
            'VNPAY_TMN_CODE' => $this->tmnCode,
            'VNPAY_HASH_SECRET' => $this->hashSecret,
            'VNPAY_URL' => $this->url,
            'VNPAY_RETURN_URL' => $this->returnUrl,
        ])
            ->filter(fn (string $value): bool => trim($value) === '')
            ->keys()
            ->all();

        if ($missing !== []) {
            throw new RuntimeException('Missing VNPay configuration: '.implode(', ', $missing));
        }
    }

    /**
     * @param  array<string, mixed>  $params
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
        DB::transaction(function () use ($order, $params, $adminId): void {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->with('items')
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedOrder->status, ['paid', 'paid_stock_issue'], true)) {
                return;
            }

            if ($lockedOrder->status !== 'pending') {
                return;
            }

            $receivedAmount = $this->receivedAmount($params);
            $items = $lockedOrder->items;
            $variantIds = $items->pluck('product_variant_id')->filter()->unique()->values();

            // Keep variant locks in a stable order to reduce deadlock risk for multi-variant orders.
            $variants = ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $hasStockIssue = $items->contains(function ($item) use ($variants): bool {
                $variant = $variants->get($item->product_variant_id);

                return ! $variant || $variant->stock_quantity < $item->quantity;
            });

            if ($hasStockIssue) {
                $lockedOrder->update([
                    'status' => 'paid_stock_issue',
                    'paid_at' => $lockedOrder->paid_at ?? now(),
                ]);

                $this->createTransaction($lockedOrder, $params, $receivedAmount, $adminId);

                return;
            }

            foreach ($items as $item) {
                $variant = $variants->get($item->product_variant_id);
                $stockAfter = $variant->stock_quantity - $item->quantity;

                $variant->update(['stock_quantity' => $stockAfter]);

                StockMovement::query()->create([
                    'product_variant_id' => $variant->id,
                    'type' => 'order_paid',
                    'quantity_change' => -1 * $item->quantity,
                    'stock_after' => $stockAfter,
                    'order_id' => $lockedOrder->id,
                    'note' => "Order {$lockedOrder->code} paid via VNPay.",
                    'created_by' => $adminId,
                ]);
            }

            $lockedOrder->update([
                'status' => 'paid',
                'paid_at' => $lockedOrder->paid_at ?? now(),
            ]);

            $this->createTransaction($lockedOrder, $params, $receivedAmount, $adminId);
            $this->clearPurchasedCartItems($lockedOrder);
        });
    }

    public function clearPurchasedCartItems(Order $order): void
    {
        $order->loadMissing('items');
        $purchasedVariantIds = $order->items->pluck('product_variant_id')->filter()->values();

        if ($purchasedVariantIds->isEmpty()) {
            return;
        }

        $cart = Cart::query()
            ->where('user_id', $order->user_id)
            ->lockForUpdate()
            ->first();

        $cart?->items()
            ->whereIn('product_variant_id', $purchasedVariantIds)
            ->delete();
    }

    /** @param array<string, mixed> $params */
    public function processFailedPayment(Order $order, array $params): void
    {
        DB::transaction(function () use ($order, $params): void {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== 'pending') {
                return;
            }

            $receivedAmount = $this->receivedAmount($params);

            $lockedOrder->update([
                'status' => 'failed',
                'paid_at' => null,
            ]);

            $this->createTransaction($lockedOrder, $params, $receivedAmount);
        });
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

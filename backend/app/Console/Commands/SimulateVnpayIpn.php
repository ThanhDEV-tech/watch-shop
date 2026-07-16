<?php

namespace App\Console\Commands;

use App\Http\Controllers\VnpayController;
use App\Models\Order;
use App\Services\VnpayService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class SimulateVnpayIpn extends Command
{
    protected $signature = 'vnpay:simulate-ipn {order_id : ID của order cần giả lập thanh toán}';

    protected $description = 'Giả lập callback IPN thành công từ VNPay cho một order';

    public function __construct(
        private readonly VnpayService $vnpayService,
        private readonly VnpayController $vnpayController,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $order = Order::query()->find($this->argument('order_id'));

        if (! $order) {
            $this->error('Order not found.');

            return self::FAILURE;
        }

        $params = $this->vnpayService->signParams([
            'vnp_Amount' => (string) ((int) round((float) $order->total_amount * 100)),
            'vnp_TxnRef' => $order->code,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_BankCode' => 'NCB',
            'vnp_TransactionNo' => (string) random_int(10000000, 99999999),
            'vnp_PayDate' => now('Asia/Ho_Chi_Minh')->format('YmdHis'),
        ]);

        $request = Request::create('/api/payment/vnpay/ipn', 'POST', $params);
        $response = $this->vnpayController->handleIpn($request);
        $responseData = json_decode((string) $response->getContent(), true);

        $this->newLine();
        $this->info('IPN response:');
        $this->line((string) $response->getContent());

        $order->refresh()
            ->load('user.cart.items:id,cart_id,course_id')
            ->loadCount(['items', 'vnpayTransactions', 'enrollments']);
        $remainingCartCourseIds = $order->user->cart?->items
            ->pluck('course_id')
            ->implode(', ') ?: '-';

        $this->newLine();
        $this->info('Order after IPN:');
        $this->table(
            ['ID', 'Code', 'Status', 'Paid at', 'Items', 'Transactions', 'Enrollments', 'Cart course IDs'],
            [[
                $order->id,
                $order->code,
                $order->status,
                $order->paid_at?->toDateTimeString() ?? '-',
                $order->items_count,
                $order->vnpay_transactions_count,
                $order->enrollments_count,
                $remainingCartCourseIds,
            ]],
        );

        return ($responseData['RspCode'] ?? null) === '00'
            ? self::SUCCESS
            : self::FAILURE;
    }
}

<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VnpayPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_vnpay_url_for_own_pending_order(): void
    {
        config()->set('services.vnpay', [
            'tmn_code' => 'TESTCODE',
            'hash_secret' => 'test-hash-secret',
            'url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'return_url' => 'http://localhost:5173/checkout/vnpay-return',
            'ipn_url' => 'https://example.test/api/payment/vnpay/ipn',
        ]);

        $studentRole = Role::query()->create([
            'name' => 'student',
            'display_name' => 'Student',
        ]);
        $student = User::factory()->create(['role_id' => $studentRole->id]);
        $order = Order::query()->create([
            'code' => 'ORD20260704000000TEST01',
            'user_id' => $student->id,
            'total_amount' => 250000,
            'status' => 'pending',
        ]);
        Sanctum::actingAs($student);

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '::1'])
            ->postJson('/api/payment/vnpay/create', [
                'order_id' => $order->id,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Tạo URL thanh toán VNPay thành công.');

        $paymentUrl = $response->json('data.payment_url');

        $this->assertIsString($paymentUrl);
        $this->assertStringStartsWith(config('services.vnpay.url').'?', $paymentUrl);
        $this->assertStringContainsString('vnp_SecureHash=', $paymentUrl);
        $this->assertStringContainsString('vnp_TxnRef='.$order->code, $paymentUrl);

        $query = (string) parse_url($paymentUrl, PHP_URL_QUERY);
        [$hashData, $secureHash] = explode('&vnp_SecureHash=', $query, 2);

        $this->assertSame(hash_hmac('sha512', $hashData, 'test-hash-secret'), $secureHash);
        $this->assertStringContainsString('vnp_Amount=25000000', $hashData);
        $this->assertStringContainsString('vnp_ExpireDate=', $hashData);
        $this->assertStringContainsString('vnp_IpAddr=127.0.0.1', $hashData);
    }
}

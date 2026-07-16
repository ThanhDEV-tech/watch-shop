<?php

namespace Tests\Feature;

use App\Mail\OrderPaidMail;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Course;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VnpayIpnTest extends TestCase
{
    use RefreshDatabase;

    private const HASH_SECRET = 'test-ipn-hash-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.vnpay.hash_secret', self::HASH_SECRET);
    }

    public function test_ipn_rejects_invalid_signature(): void
    {
        $this->postJson('/api/payment/vnpay/ipn', [
            'vnp_Amount' => '29900000',
            'vnp_ResponseCode' => '00',
            'vnp_TxnRef' => 'ORDINVALIDSIGNATURE',
            'vnp_SecureHash' => str_repeat('0', 128),
        ])
            ->assertOk()
            ->assertExactJson([
                'RspCode' => '97',
                'Message' => 'Invalid signature',
            ]);

        $this->assertDatabaseCount('vnpay_transactions', 0);
    }

    public function test_ipn_returns_order_not_found_for_valid_signature(): void
    {
        $params = $this->signParams($this->baseParams('ORDDOESNOTEXIST', '29900000'));

        $this->postJson('/api/payment/vnpay/ipn', $params)
            ->assertOk()
            ->assertExactJson([
                'RspCode' => '01',
                'Message' => 'Order not found',
            ]);
    }

    public function test_ipn_rejects_invalid_amount_without_updating_order(): void
    {
        [$order] = $this->createOrderWithCourses(299000, 1);
        $params = $this->signParams($this->baseParams($order->code, '10000000'));

        $this->postJson('/api/payment/vnpay/ipn', $params)
            ->assertOk()
            ->assertExactJson([
                'RspCode' => '04',
                'Message' => 'Invalid amount',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
            'paid_at' => null,
        ]);
        $this->assertDatabaseCount('vnpay_transactions', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_ipn_is_idempotent_when_order_was_already_confirmed(): void
    {
        Mail::fake();
        [$order] = $this->createOrderWithCourses(299000, 1);
        $params = $this->signParams($this->baseParams($order->code, '29900000'));

        $this->postJson('/api/payment/vnpay/ipn', $params)
            ->assertOk()
            ->assertJsonPath('RspCode', '00');

        $this->postJson('/api/payment/vnpay/ipn', $params)
            ->assertOk()
            ->assertExactJson([
                'RspCode' => '02',
                'Message' => 'Order already confirmed',
            ]);

        $this->assertDatabaseCount('vnpay_transactions', 1);
        $this->assertDatabaseCount('enrollments', 1);
        Mail::assertQueuedCount(1);
    }

    public function test_successful_ipn_pays_order_logs_transaction_and_enrolls_all_courses(): void
    {
        Mail::fake();
        [$order, $courses] = $this->createOrderWithCourses(299000, 2);
        $extraCourse = $this->createCourse();
        $cart = Cart::query()->create(['user_id' => $order->user_id]);

        foreach ([...$courses, $extraCourse] as $course) {
            $cart->items()->create([
                'course_id' => $course->id,
                'price_snapshot' => $course->price,
            ]);
        }

        $params = $this->signParams($this->baseParams($order->code, '29900000'));

        $this->postJson('/api/payment/vnpay/ipn', $params)
            ->assertOk()
            ->assertExactJson([
                'RspCode' => '00',
                'Message' => 'Confirm Success',
            ]);

        $order->refresh();

        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertDatabaseHas('vnpay_transactions', [
            'order_id' => $order->id,
            'txn_ref' => $order->code,
            'amount' => 299000,
            'bank_code' => 'NCB',
            'transaction_no' => '15609817',
            'response_code' => '00',
            'transaction_status' => '00',
            'is_verified' => true,
        ]);
        $this->assertDatabaseCount('vnpay_transactions', 1);

        foreach ($courses as $course) {
            $this->assertDatabaseHas('enrollments', [
                'user_id' => $order->user_id,
                'course_id' => $course->id,
                'order_id' => $order->id,
            ]);
        }

        $this->assertDatabaseCount('enrollments', 2);
        $this->assertSame($params, $order->vnpayTransactions()->firstOrFail()->raw_response);
        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
            'course_id' => $courses[0]->id,
        ]);
        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
            'course_id' => $courses[1]->id,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'course_id' => $extraCourse->id,
        ]);
        $this->assertDatabaseCount('cart_items', 1);
        Mail::assertQueued(OrderPaidMail::class, function (OrderPaidMail $mail) use ($order): bool {
            return $mail->order->is($order)
                && $mail->hasTo($order->user->email);
        });
    }

    /** @return array{Order, array<int, Course>} */
    private function createOrderWithCourses(
        float $totalAmount,
        int $courseCount,
        string $status = 'pending',
    ): array {
        $student = $this->createUser('student');
        $order = Order::query()->create([
            'code' => 'ORD'.now()->format('YmdHis').str()->upper(str()->random(6)),
            'user_id' => $student->id,
            'total_amount' => $totalAmount,
            'status' => $status,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);
        $courses = [];

        for ($index = 0; $index < $courseCount; $index++) {
            $course = $this->createCourse();
            $courses[] = $course;
            $order->items()->create([
                'course_id' => $course->id,
                'price' => $totalAmount / $courseCount,
            ]);
        }

        return [$order, $courses];
    }

    private function createCourse(): Course
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'vnpay-testing'],
            ['name' => 'VNPay Testing'],
        );

        return Course::query()->create([
            'instructor_id' => $this->createUser('instructor')->id,
            'category_id' => $category->id,
            'title' => 'Course '.str()->random(8),
            'slug' => 'course-'.str()->random(8),
            'price' => 149500,
            'status' => 'approved',
            'published_at' => now(),
        ]);
    }

    private function createUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    /** @return array<string, string> */
    private function baseParams(string $orderCode, string $amount): array
    {
        return [
            'vnp_Amount' => $amount,
            'vnp_BankCode' => 'NCB',
            'vnp_BankTranNo' => 'VNP15609817',
            'vnp_CardType' => 'ATM',
            'vnp_PayDate' => '20260704105614',
            'vnp_ResponseCode' => '00',
            'vnp_TmnCode' => 'TESTCODE',
            'vnp_TransactionNo' => '15609817',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => $orderCode,
        ];
    }

    /** @param array<string, string> $params
     * @return array<string, string>
     */
    private function signParams(array $params): array
    {
        ksort($params);

        $hashData = implode('&', array_map(
            fn (string $key, string $value): string => urlencode($key).'='.urlencode($value),
            array_keys($params),
            array_values($params),
        ));

        $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, self::HASH_SECRET);

        return $params;
    }
}

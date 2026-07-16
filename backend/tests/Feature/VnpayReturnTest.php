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

class VnpayReturnTest extends TestCase
{
    use RefreshDatabase;

    private const HASH_SECRET = 'test-return-hash-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.vnpay.hash_secret', self::HASH_SECRET);
    }

    public function test_valid_success_return_pays_order_and_creates_enrollment(): void
    {
        Mail::fake();
        [$order, $courses] = $this->createOrderWithCourses(250000, 2);
        $extraCourse = $this->createCourse();
        $cart = Cart::query()->create(['user_id' => $order->user_id]);

        foreach ([...$courses, $extraCourse] as $course) {
            $cart->items()->create([
                'course_id' => $course->id,
                'price_snapshot' => $course->price,
            ]);
        }

        $params = $this->signParams($this->baseParams($order->code, '25000000'));

        $response = $this->getJson('/api/payment/vnpay/return?'.http_build_query($params));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.response_code', '00')
            ->assertJsonPath('data.order_code', $order->code)
            ->assertJsonPath('data.amount', 250000)
            ->assertJsonPath('data.is_success', true)
            ->assertJsonPath('data.processing_status', 'processed')
            ->assertJsonPath('message', 'Thanh toán thành công.');

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertDatabaseCount('vnpay_transactions', 1);

        foreach ($courses as $course) {
            $this->assertDatabaseHas('enrollments', [
                'user_id' => $order->user_id,
                'course_id' => $course->id,
                'order_id' => $order->id,
            ]);
            $this->assertDatabaseMissing('cart_items', [
                'cart_id' => $cart->id,
                'course_id' => $course->id,
            ]);
        }

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'course_id' => $extraCourse->id,
        ]);

        Mail::assertQueued(OrderPaidMail::class);
    }

    public function test_success_return_is_idempotent_when_user_refreshes_return_url(): void
    {
        Mail::fake();
        [$order, $courses] = $this->createOrderWithCourses(250000, 1);
        $params = $this->signParams($this->baseParams($order->code, '25000000'));
        $url = '/api/payment/vnpay/return?'.http_build_query($params);
        $cart = Cart::query()->create(['user_id' => $order->user_id]);

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.processing_status', 'processed');

        $cart->items()->create([
            'course_id' => $courses[0]->id,
            'price_snapshot' => $courses[0]->price,
        ]);

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.is_success', true)
            ->assertJsonPath('data.processing_status', 'already_processed');

        $this->assertDatabaseCount('vnpay_transactions', 1);
        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
            'course_id' => $courses[0]->id,
        ]);
        Mail::assertQueuedCount(1);
    }

    public function test_success_return_with_invalid_amount_does_not_update_order(): void
    {
        Mail::fake();
        [$order] = $this->createOrderWithCourses(250000, 1);
        $params = $this->signParams($this->baseParams($order->code, '10000000'));

        $this->getJson('/api/payment/vnpay/return?'.http_build_query($params))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_success', false)
            ->assertJsonPath('data.processing_status', 'invalid_amount')
            ->assertJsonPath('message', 'Số tiền thanh toán không khớp với đơn hàng.');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
            'paid_at' => null,
        ]);
        $this->assertDatabaseCount('vnpay_transactions', 0);
        $this->assertDatabaseCount('enrollments', 0);
        Mail::assertNothingQueued();
    }

    public function test_return_with_invalid_signature_is_rejected(): void
    {
        $params = [
            'vnp_Amount' => '25000000',
            'vnp_ResponseCode' => '00',
            'vnp_TxnRef' => 'ORDINVALIDSIGNATURE',
            'vnp_SecureHash' => str_repeat('0', 128),
        ];

        $this->getJson('/api/payment/vnpay/return?'.http_build_query($params))
            ->assertBadRequest()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Chữ ký không hợp lệ.');
    }

    /** @return array{Order, array<int, Course>} */
    private function createOrderWithCourses(float $totalAmount, int $courseCount): array
    {
        $student = $this->createUser('student');
        $order = Order::query()->create([
            'code' => 'ORD'.now()->format('YmdHis').str()->upper(str()->random(6)),
            'user_id' => $student->id,
            'total_amount' => $totalAmount,
            'status' => 'pending',
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
            ['slug' => 'vnpay-return-testing'],
            ['name' => 'VNPay Return Testing'],
        );

        return Course::query()->create([
            'instructor_id' => $this->createUser('instructor')->id,
            'category_id' => $category->id,
            'title' => 'Course '.str()->random(8),
            'slug' => 'course-'.str()->random(8),
            'price' => 125000,
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

    /**
     * @param  array<string, string>  $params
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

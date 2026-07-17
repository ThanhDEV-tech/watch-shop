<?php

use App\Http\Controllers\Api\AdminCourseController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CertificationController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CurriculumController;
use App\Http\Controllers\Api\InstructorDashboardController;
use App\Http\Controllers\Api\LearningController;
use App\Http\Controllers\Api\MyOrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\PublicLandingController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\VnpayController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/avatar', [AuthController::class, 'updateAvatar']);
        Route::put('/password', [AuthController::class, 'updatePassword']);
    });
});

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/brands', [BrandController::class, 'publicIndex']);
Route::get('/collections', [CollectionController::class, 'publicIndex']);
Route::get('/products', [ProductController::class, 'publicIndex']);
Route::get('/certifications', [CertificationController::class, 'index']);
Route::get('/certifications/{certification}', [CertificationController::class, 'show']);
Route::get('/stats/public', [PublicLandingController::class, 'stats']);
Route::get('/instructors/featured', [PublicLandingController::class, 'featuredInstructors']);
Route::get('/instructors/{instructor}/courses', [CourseController::class, 'instructorCourses']);
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{course}/related', [CourseController::class, 'related']);
Route::get('/courses/{course}', [CourseController::class, 'show']);
Route::get('/courses/{course}/chapters', [CurriculumController::class, 'index']);
Route::get('/courses/{course}/reviews', [ReviewController::class, 'index']);

Route::prefix('payment/vnpay')->group(function () {
    Route::post('/create', [VnpayController::class, 'createPayment'])->middleware('auth:sanctum');
    Route::get('/return', [VnpayController::class, 'handleReturn']);
    Route::post('/ipn', [VnpayController::class, 'handleIpn']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ai/chat', [AiChatController::class, 'chat']);
    Route::get('/ai/sessions/{session}/messages', [AiChatController::class, 'messages']);
    Route::get('/my-courses', [LearningController::class, 'myCourses']);
    Route::get('/my-orders', [MyOrderController::class, 'index']);
    Route::get('/courses/{course}/lessons/{lesson}', [LearningController::class, 'showLesson']);
    Route::post('/lessons/{lesson}/complete', [LearningController::class, 'completeLesson']);
    Route::match(['post', 'put'], '/courses/{course}/reviews', [ReviewController::class, 'store']);
    Route::delete('/courses/{course}/reviews/{review}', [ReviewController::class, 'destroy']);
    Route::get('/lessons/{lesson}/comments', [CommentController::class, 'index']);
    Route::post('/lessons/{lesson}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'store']);
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy']);
    Route::post('/checkout', [CheckoutController::class, 'store']);
});

Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
    Route::get('/users', [AdminDashboardController::class, 'users']);
    Route::patch('/users/{user}/toggle-active', [AdminDashboardController::class, 'toggleActive']);
    Route::get('/orders', [AdminDashboardController::class, 'orders']);
    Route::post('/orders/{order}/mark-as-paid', [AdminDashboardController::class, 'markOrderAsPaid']);
    Route::post('/orders/{order}/mark-as-refunded', [AdminDashboardController::class, 'markOrderAsRefunded']);
    Route::get('/orders/{order}', [AdminDashboardController::class, 'order'])
        ->missing(fn () => response()->json([
            'success' => false,
            'data' => null,
            'message' => 'Không tìm thấy đơn hàng.',
        ], 404));
    Route::get('/vnpay-transactions', [AdminDashboardController::class, 'vnpayTransactions']);
    Route::get('/stock-movements', [AdminDashboardController::class, 'stockMovements']);
    Route::get('/categories', [CategoryController::class, 'adminIndex']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::match(['put', 'patch'], '/categories/{category}', [CategoryController::class, 'update']);
    Route::patch('/categories/{category}/toggle-active', [CategoryController::class, 'toggleActive']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::get('/brands', [BrandController::class, 'index']);
    Route::post('/brands', [BrandController::class, 'store']);
    Route::match(['put', 'patch'], '/brands/{brand}', [BrandController::class, 'update']);
    Route::delete('/brands/{brand}', [BrandController::class, 'destroy']);
    Route::get('/collections', [CollectionController::class, 'index']);
    Route::post('/collections', [CollectionController::class, 'store']);
    Route::match(['put', 'patch'], '/collections/{collection}', [CollectionController::class, 'update']);
    Route::delete('/collections/{collection}', [CollectionController::class, 'destroy']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::match(['put', 'patch'], '/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::get('/product-variants', [ProductVariantController::class, 'index']);
    Route::post('/product-variants', [ProductVariantController::class, 'store']);
    Route::get('/product-variants/{productVariant}', [ProductVariantController::class, 'show']);
    Route::match(['put', 'patch'], '/product-variants/{productVariant}', [ProductVariantController::class, 'update']);
    Route::delete('/product-variants/{productVariant}', [ProductVariantController::class, 'destroy']);
    Route::get('/certifications', [CertificationController::class, 'adminIndex']);
    Route::post('/certifications', [CertificationController::class, 'store']);
    Route::post('/certifications/{certification}/regenerate-badge', [CertificationController::class, 'regenerateBadge']);
    Route::match(['put', 'patch'], '/certifications/{certification}', [CertificationController::class, 'update']);
    Route::delete('/certifications/{certification}', [CertificationController::class, 'destroy']);
    Route::get('/courses', [AdminCourseController::class, 'index']);
    Route::get('/courses/{course}', [AdminCourseController::class, 'show']);
    Route::patch('/courses/{course}/approve', [AdminCourseController::class, 'approve']);
    Route::patch('/courses/{course}/reject', [AdminCourseController::class, 'reject']);
    Route::patch('/courses/{course}/status', [AdminCourseController::class, 'updateStatus']);
});

Route::prefix('instructor')->middleware(['auth:sanctum', 'role:instructor'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dashboard/stats', [InstructorDashboardController::class, 'stats']);
    Route::get('/courses/{course}/students', [InstructorDashboardController::class, 'students']);
    Route::get('/courses', [CourseController::class, 'instructorIndex']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::post('/courses/{course}/submit', [CourseController::class, 'submit']);
    Route::match(['put', 'patch'], '/courses/{course}', [CourseController::class, 'update']);
    Route::delete('/courses/{course}', [CourseController::class, 'destroy']);
});

Route::prefix('instructor')->middleware('auth:sanctum')->group(function () {
    Route::post('/courses/{course}/chapters', [CurriculumController::class, 'storeChapter']);
    Route::patch('/courses/{course}/chapters/reorder', [CurriculumController::class, 'reorderChapters']);
    Route::match(['put', 'patch'], '/chapters/{chapter}', [CurriculumController::class, 'updateChapter']);
    Route::delete('/chapters/{chapter}', [CurriculumController::class, 'destroyChapter']);
    Route::post('/chapters/{chapter}/lessons', [CurriculumController::class, 'storeLesson']);
    Route::patch('/chapters/{chapter}/lessons/reorder', [CurriculumController::class, 'reorderLessons']);
    Route::match(['put', 'patch'], '/lessons/{lesson}', [CurriculumController::class, 'updateLesson']);
    Route::delete('/lessons/{lesson}', [CurriculumController::class, 'destroyLesson']);
});

Route::prefix('student')->middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
});

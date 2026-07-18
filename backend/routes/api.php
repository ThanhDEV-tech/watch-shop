<?php

use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\MyOrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\ShippingZoneController;
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
Route::get('/products/{slug}', [ProductController::class, 'publicShow']);
Route::get('/shipping-zones', [ShippingZoneController::class, 'publicIndex']);

Route::prefix('payment/vnpay')->group(function () {
    Route::post('/create', [VnpayController::class, 'createPayment'])->middleware('auth:sanctum');
    Route::get('/return', [VnpayController::class, 'handleReturn']);
    Route::post('/ipn', [VnpayController::class, 'handleIpn']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ai/chat', [AiChatController::class, 'chat']);
    Route::get('/ai/sessions/{session}/messages', [AiChatController::class, 'messages']);
    Route::get('/my-orders', [MyOrderController::class, 'index']);
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'store']);
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update']);
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
    Route::get('/shipping-zones', [ShippingZoneController::class, 'index']);
    Route::post('/shipping-zones', [ShippingZoneController::class, 'store']);
    Route::match(['put', 'patch'], '/shipping-zones/{shippingZone}', [ShippingZoneController::class, 'update']);
    Route::patch('/shipping-zones/{shippingZone}/toggle-active', [ShippingZoneController::class, 'toggleActive']);
    Route::delete('/shipping-zones/{shippingZone}', [ShippingZoneController::class, 'destroy']);
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
});

Route::prefix('student')->middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
});

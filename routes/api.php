<?php

use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function ($router) {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    //Đơn hàng
    Route::get('/orders/{id}', [OrderController::class, 'getOrderDetails'])->middleware('auth:api');
    Route::get('/get-orders', [OrderController::class, 'getOrders'])->middleware('auth:api');
    //Thông tin tài khoản
    Route::get('/profile', [UserController::class, 'index'])->middleware('auth:api');
    Route::put('/{id}', [UserController::class, 'update'])->middleware('auth:api'); // Cập nhật thông tin người dùng
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'products',
], function ($router) {
    Route::get('/vouchers', [VoucherController::class, 'getVoucher']);
    Route::post('/vouchers/store-user', [VoucherController::class, 'storeUserVoucher'])->middleware('auth:api');

    Route::get('/', [ProductController::class, 'index']);
    Route::get('/search', [ProductController::class, 'search']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::post('/{productId}/comment', [CommentController::class, 'store'])->middleware('auth:api');
});
Route::post('comments/{commentId}/toggleLike', [CommentController::class, 'toggleLike'])->middleware('auth:api');
Route::get('comments/{productId}', [CommentController::class, 'show']);


Route::group([
    'middleware' => 'api',
    'prefix' => 'categories',
], function ($router) {

    Route::get('/', [CategoriesController::class, 'index']);
});

Route::post('/checkout', [OrderController::class, 'checkout'])->middleware('auth:api');
Route::get('/info-checkout/{orderId}', [OrderController::class, 'infoCheckout'])->middleware('auth:api');
Route::post('/payment', [PaymentController::class, 'processPayment'])->middleware('auth:api');


// Demo phân quyền
// Route::group([
//     'middleware' => ['api','admin'],
//     'prefix' => 'admin',
// ], function ($router){
// });


Route::middleware(['auth:api', 'admin'])->group(function () {
    Route::get('admin/dashboard', [DashboardController::class, 'index']);
    // Route::get('admin/product/{id}',     [AdminProductController::class, 'update']);
    Route::put('admin/product/{id}', [AdminProductController::class, 'update']);
});


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

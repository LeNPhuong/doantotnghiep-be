<?php
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminCommentController;
use App\Http\Controllers\Admin\AdminOrderController;
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
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminStatusController;
use App\Http\Controllers\Admin\AdminUnitsController;
use App\Http\Controllers\PasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function ($router) {
    //Đăng ký
    Route::post('/register', [AuthController::class, 'register']);
    //Đăng nhập
    Route::post('/login', [AuthController::class, 'login']);
    //Đăng xuất
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
    //Làm mới token
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    //Đơn hàng
    //Lấy chi tiết đơn hàng
    Route::get('/orders/{orderId}/details', [OrderController::class, 'getOrderDetails'])->middleware('auth:api');
    //Lấy tất cả đơn hàng
    Route::get('/get-orders', [OrderController::class, 'getOrders'])->middleware('auth:api');

    //Thông tin tài khoản
    //Xem thông tin tài khoản
    Route::get('/profile', [UserController::class, 'index'])->middleware('auth:api');
    //Thay đổi thông tin tài khoản
    Route::post('/update-profile', [UserController::class, 'update'])->middleware('auth:api'); // Cập nhật thông tin người dùng

    //Lấy tất cả địa chỉ tài khoản
    Route::get('/address/all', [AddressController::class, 'index'])->middleware('auth:api');
    //Tạo thêm địa chỉ cho tài khoản
    Route::post('/address/create', [AddressController::class, 'store'])->middleware('auth:api');
    //Thay đổi thông tin của 1 địa chỉ cụ thể
    Route::put('/address/{id}', [AddressController::class, 'update'])->middleware('auth:api');
    //Thay đổi password của tài khoản
    Route::post('/change-password', [PasswordController::class, 'changePassword'])->middleware('auth:api');
});
//Xác thực email để nhận mã otp
Route::post('forgot-password/send-otp', [PasswordController::class, 'sendOtp']);
//Check OTP chỉ tồn tại trong 1p
Route::post('forgot-password/verify-otp', [PasswordController::class, 'verifyOtp']);
//Sau khi check OTP rồi reset password
Route::post('forgot-password/reset-password', [PasswordController::class, 'resetPassword']);


Route::group([
    'middleware' => 'api',
    'prefix' => 'products',
], function ($router) {
    //Lấy tất cả voucher đang có
    Route::get('/vouchers', [VoucherController::class, 'getVoucher']);
    //Mỗi user sẽ lấy được 1 voucher duy nhất của mỗi loại
    Route::post('/vouchers/store-user', [VoucherController::class, 'storeUserVoucher'])->middleware('auth:api');
    //Lấy tất cả sản phẩm
    Route::get('/', [ProductController::class, 'index']);
    //Tìm kiếm sản phẩm theo tên
    Route::post('/search', [ProductController::class, 'search']);
    //Lấy thông tin chi tiết của 1 sản phẩm
    Route::get('/{id}', [ProductController::class, 'show']);
    //tạo ra comment cho từng sản phẩm
    Route::post('/{productId}/comment', [CommentController::class, 'store'])->middleware('auth:api');
});
//Like hoặc bỏ like comment
Route::post('comments/{commentId}/toggleLike', [CommentController::class, 'toggleLike'])->middleware('auth:api');
//Lấy tất cả comment của 1 sản phẩm
Route::get('comments/{productId}', [CommentController::class, 'show']);


Route::group([
    'middleware' => 'api',
    'prefix' => 'categories',
], function ($router) {
    //Lấy tất cả danh mục sản phẩm
    Route::get('/', [CategoriesController::class, 'index']);
    Route::get('/{id}/products', [CategoriesController::class, 'getProductsByCategory']);

});
//Mua hàng
Route::post('/checkout', [OrderController::class, 'checkout'])->middleware('auth:api');
//Lấy chi tiết 1 đơn hàng đã checkout
Route::get('/info-checkout/{orderId}', [OrderController::class, 'infoCheckout'])->middleware('auth:api');
//Thanh toán
Route::post('/payment', [PaymentController::class, 'processPayment'])->middleware('auth:api');
//Tìm mã code của đơn hàng
Route::post('/orders/code', [OrderController::class, 'getOrderByCode'])->middleware('auth:api');
//Hủy đơn hàng cụ thể
Route::delete('/orders/{orderId}/cancel', [OrderController::class, 'cancelOrder'])->middleware('auth:api');

Route::post('/test', [PaymentController::class, 'test']);


// Demo phân quyền
// Route::group([
//     'middleware' => ['api','admin'],
//     'prefix' => 'admin',
// ], function ($router){
// });


Route::middleware(['auth:api', 'admin'])->group(function () {

    Route::get('admin/dashboard', [DashboardController::class, 'index']);

    // Product
    Route::get('admin/products',[AdminProductController::class, 'index']);
    Route::get('admin/product/search',[AdminProductController::class, 'search']);
    Route::get('admin/product/{id}',[AdminProductController::class, 'show']);
    Route::get('admin/product/{id}/update',[AdminProductController::class, 'edit']);
    Route::post('admin/product/{id}/update', [AdminProductController::class, 'update']);
    Route::delete('admin/product/{id}/soft-delete', [AdminProductController::class, 'softDelete']);
    Route::patch('admin/product/{id}/restore', [AdminProductController::class, 'restore']);
    Route::post('admin/product/create', [AdminProductController::class, 'create']);
    
    // List danh mục
    Route::get('admin/categories',[AdminCategoryController::class, 'index']);
    Route::post('admin/category/create', [AdminCategoryController::class, 'create']);
    Route::get('admin/category/{id}',[AdminCategoryController::class, 'show']);
    Route::get('admin/category/{id}/edit',[AdminCategoryController::class, 'edit']);
    Route::put('admin/category/{id}/update', [AdminCategoryController::class, 'update']);
    Route::get('admin/categories/search',[AdminCategoryController::class, 'search']);
    Route::delete('admin/category/{id}/soft-delete', [AdminCategoryController::class, 'softDelete']);
    Route::patch('admin/category/{id}/restore', [AdminCategoryController::class, 'restore']);
    
    // user 
    Route::get('admin/users',[AdminUserController::class, 'index']);
    Route::get('admin/user/search',[AdminUserController::class, 'search']);
    Route::get('admin/user/{id}',[AdminUserController::class, 'show']);
    Route::get('admin/user/{id}/edit', [AdminUserController::class, 'edit']);
    Route::put('admin/user/{id}/update',[AdminUserController::class, 'update']);
    Route::delete('admin/user/{id}/delete',[AdminUserController::class, 'softDelete']);
    Route::patch('admin/user/{id}/restore',[AdminUserController::class, 'restore']);
    Route::post('admin/user/create',[AdminUserController::class, 'create']);

    // Quản lý đơn vị 
    Route::get('admin/units',[AdminUnitsController::class, 'index']);
    Route::get('admin/units/search',[AdminUnitsController::class, 'search']);
    Route::get('admin/units/edit/{id}',[AdminUnitsController::class, 'edit']);
    Route::put('admin/units/update/{id}',[AdminUnitsController::class, 'update']);
    Route::delete('admin/units/delete/{id}',[AdminUnitsController::class, 'delete']);
    Route::patch('admin/units/restore/{id}',[AdminUnitsController::class, 'restore']);
    Route::post('admin/units/create',[AdminUnitsController::class, 'create']);

    // Quản lý Status
    Route::get('admin/status',[AdminStatusController::class, 'index']);
    Route::get('admin/status/search',[AdminStatusController::class, 'search']);
    Route::get('admin/status/edit/{id}',[AdminStatusController::class, 'edit']);
    Route::put('admin/status/update/{id}',[AdminStatusController::class, 'update']);
    Route::delete('admin/status/delete/{id}',[AdminStatusController::class, 'delete']);
    Route::patch('admin/status/restore/{id}',[AdminStatusController::class, 'restore']);
    Route::post('admin/status/create',[AdminStatusController::class, 'create']);
    
    // Quản lý comment
    Route::get('admin/comments/{id}',[AdminCommentController::class, 'index']);
    Route::get('admin/comment/search',[AdminCommentController::class, 'search']);
    Route::delete('admin/comments/delete/{id}',[AdminCommentController::class, 'delete']);
    Route::patch('admin/comments/restore/{id}',[AdminCommentController::class, 'restore']);
    
    // Quản lý đơn hàng
    Route::get('admin/orders',[AdminOrderController::class, 'index']);
    Route::get('admin/orders/search',[AdminOrderController::class, 'search']);
    Route::get('admin/orders/{id}',[AdminOrderController::class, 'show']);
    Route::get('admin/print/{id}',[AdminOrderController::class, 'print']);
    Route::put('admin/orders/confirm/{id}',[AdminOrderController::class, 'confirm']);
    Route::put('admin/orders/cancel/{id}',[AdminOrderController::class, 'cancel']);


});


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

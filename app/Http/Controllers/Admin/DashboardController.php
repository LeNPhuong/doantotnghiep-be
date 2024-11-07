<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Product;
use Carbon\Carbon;
use Exception;

class DashboardController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/admin/dashboard",
     *     summary="Lấy dữ liệu cho trang dashboard",
     *     description="Trả về tổng số đơn hàng, tổng doanh thu, số sản phẩm đã bán, thành viên mới trong tuần, sản phẩm hot và doanh thu trong tuần",
     *     tags={"admin/dashboard"},
     *     security={{"bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Truy xuất dữ liệu dashboard thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_orders", type="integer", example=150),
     *             @OA\Property(property="total_revenue", type="number", format="float", example=1250000.50),
     *             @OA\Property(property="total_products_sold", type="integer", example=300),
     *             @OA\Property(property="new_members", type="integer", example=10),
     *             @OA\Property(
     *                 property="hot_products",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Sản phẩm A"),
     *                     @OA\Property(property="price", type="number", format="float", example=100.5),
     *                     @OA\Property(property="quantity_sold", type="integer", example=20)
     *                 )
     *             ),
     *             @OA\Property(property="weekly_revenue", type="number", format="float", example=250000.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi định dạng.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Chi tiết lỗi...")
     *         )
     *     )
     * )
     */

    public function index()
    {
        try {
            // Khởi tạo mảng để chứa tất cả các dữ liệu
            $dashboardData = [];

            // Tổng số đơn hàng
            $totalOrders = Order::count();
            $dashboardData['total_orders'] = $totalOrders;

            // Tổng doanh thu từ các đơn hàng đã giao (status id = 4)
            $totalRevenue = Order::where('status_id', 4)->sum('total_price');
            $dashboardData['total_revenue'] = $totalRevenue;

            // Tổng sản phẩm đã bán
            $totalProductsSold = OrderDetail::sum('quantity');
            $dashboardData['total_products_sold'] = $totalProductsSold;

            // Thành viên mới trong tuần
            $newMembers = User::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->count();
            $dashboardData['new_members'] = $newMembers;

            // Sản phẩm hot trong tuần (Sản phẩm bán chạy nhất)
            $hotProducts = Product::select('product.*')
                ->join('order_detail', 'product.id', '=', 'order_detail.product_id')
                ->whereBetween('order_detail.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->groupBy('product.id')
                ->orderByRaw('SUM(order_detail.quantity) DESC')
                ->limit(5)
                ->get();
            $dashboardData['hot_products'] = $hotProducts;

            // Doanh thu tuần (chỉ tính đơn hàng đã giao, status id = 4)
            $weeklyRevenue = Order::where('status_id', 4)
                ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->sum('total_price');
            $dashboardData['weekly_revenue'] = $weeklyRevenue;

            // Trả về tất cả dữ liệu trong một lần
            return $this->sendResponse($dashboardData, 'Truy xuất dữ liệu dashboard thành công.');
        } catch (Exception $th) {
            // Trả về lỗi nếu xảy ra
            return $this->sendError('Lỗi định dạng.', ['error' => $th->getMessage()], 500);
        }
    }
}

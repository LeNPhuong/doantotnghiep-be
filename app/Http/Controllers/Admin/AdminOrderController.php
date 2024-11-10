<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/admin/orders",
     *     summary="Lấy danh sách đơn hàng",
     *     description="Lấy danh sách đơn hàng với các bộ lọc theo ngày bắt đầu, ngày kết thúc, và trạng thái.",
     *     tags={"admin/orders"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Ngày bắt đầu (yyyy-mm-dd)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date",
     *             example="2024-11-01"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Ngày kết thúc (yyyy-mm-dd)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date",
     *             example="2024-11-10"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Trạng thái của đơn hàng (id của trạng thái)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách đơn hàng thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy danh sách đơn hàng thành công"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=123),
     *                     @OA\Property(property="status_id", type="integer", example=2),
     *                     @OA\Property(property="voucher_id", type="integer", example=3),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T10:00:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi xảy ra trong quá trình lấy đơn hàng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra. Vui lòng thử lại sau."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="error", type="string", example="Error details")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            // Lấy giá trị từ input
            $startDate = $request->input('start_date'); // dạng yyyy-mm-dd 
            $endDate = $request->input('end_date');
            $status = $request->input('status'); // nhận id của status nha

            $query = Order::with(['user', 'status', 'voucher', 'orderDetails', 'transaction']); // Gọi các quan hệ liên quan

            // Lọc theo ngày bắt đầu
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            // Lọc theo ngày kết thúc
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            // Lọc theo trạng thái đơn hàng
            if ($status) {
                $query->where('status_id', $status);
            }

            // Lấy tất cả các đơn hàng sau khi đã áp dụng các bộ lọc
            $orders = $query->get();

            return $this->sendResponse($orders, 'Lấy danh sách đơn hàng thành công');
        } catch (\Exception $e) {
            return $this->sendError('Có lỗi xảy ra. Vui lòng thử lại sau.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/orders/search",
     *     summary="Tìm kiếm đơn hàng",
     *     description="Tìm kiếm đơn hàng dựa trên mã đơn hàng hoặc tên người dùng.",
     *     tags={"admin/orders"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Chuỗi tìm kiếm cho mã đơn hàng hoặc tên người dùng.",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="ORD123"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách đơn hàng tìm thấy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đơn hàng tìm thấy"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="code", type="string", example="ORD123"),
     *                     @OA\Property(property="user_id", type="integer", example=123),
     *                     @OA\Property(property="status_id", type="integer", example=2),
     *                     @OA\Property(property="voucher_id", type="integer", example=3),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T10:00:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy đơn hàng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy đơn hàng"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi tìm kiếm",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi trong quá trình tìm kiếm đơn hàng"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="error", type="string", example="Error details")
     *             )
     *         )
     *     )
     * )
     */
    public function search(Request $request)
    {
        try {
            $inputSearch = $request->input('query');

            $orders = Order::with(['user', 'status', 'voucher', 'orderDetails', 'transaction'])->get();

            // Tìm kiếm trong 'code' của đơn hàng và 'name' của người dùng
            $filteredOrders = $orders->filter(function ($order) use ($inputSearch) {
                return (strpos(strtolower($order->code), strtolower($inputSearch)) !== false) ||
                    (isset($order->user) && strpos(strtolower($order->user->name), strtolower($inputSearch)) !== false);
            });

            if ($filteredOrders->isEmpty()) {
                return $this->sendResponse([], 'Không tìm thấy đơn hàng');
            }

            return $this->sendResponse($filteredOrders, 'Đơn hàng tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm đơn hàng', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/orders/{id}",
     *     summary="Lấy thông tin chi tiết đơn hàng",
     *     description="Lấy thông tin chi tiết đơn hàng bao gồm người dùng, trạng thái, chi tiết đơn hàng, voucher và giao dịch.",
     *     tags={"admin/orders"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của đơn hàng",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thông tin đơn hàng được tìm thấy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đơn hàng tìm thấy"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="ORD123"),
     *                 @OA\Property(property="user_id", type="integer", example=123),
     *                 @OA\Property(property="status_id", type="integer", example=2),
     *                 @OA\Property(property="voucher_id", type="integer", example=3),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 ),
     *                 @OA\Property(property="status", type="object",
     *                     @OA\Property(property="name", type="string", example="Completed")
     *                 ),
     *                 @OA\Property(property="orderDetails", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="product", type="object",
     *                             @OA\Property(property="name", type="string", example="Product Name")
     *                         ),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", format="float", example=19.99)
     *                     )
     *                 ),
     *                 @OA\Property(property="voucher", type="object",
     *                     @OA\Property(property="code", type="string", example="DISCOUNT10")
     *                 ),
     *                 @OA\Property(property="transaction", type="object",
     *                     @OA\Property(property="amount", type="number", format="float", example=39.98),
     *                     @OA\Property(property="status", type="string", example="Success")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy đơn hàng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy đơn hàng"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi tìm kiếm đơn hàng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi trong quá trình tìm kiếm đơn hàng"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="error", type="string", example="Error details")
     *             )
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $order = Order::with(['user', 'status', 'orderDetails.product', 'voucher', 'transaction'])->find($id);

            if (!$order) {
                return $this->sendError('Không tìm thấy đơn hàng', [], 404);
            }

            return $this->sendResponse($order, 'Đơn hàng tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm đơn hàng', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/print/{id}",
     *     summary="In thông tin chi tiết đơn hàng",
     *     description="Lấy thông tin chi tiết đơn hàng bao gồm người dùng, trạng thái, chi tiết đơn hàng, voucher và giao dịch để in.",
     *     tags={"admin/orders"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của đơn hàng cần in",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thông tin đơn hàng được tìm thấy và có thể in",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đơn hàng tìm thấy"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="ORD123"),
     *                 @OA\Property(property="user_id", type="integer", example=123),
     *                 @OA\Property(property="status_id", type="integer", example=2),
     *                 @OA\Property(property="voucher_id", type="integer", example=3),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 ),
     *                 @OA\Property(property="status", type="object",
     *                     @OA\Property(property="name", type="string", example="Completed")
     *                 ),
     *                 @OA\Property(property="orderDetails", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="product", type="object",
     *                             @OA\Property(property="name", type="string", example="Product Name")
     *                         ),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", format="float", example=19.99)
     *                     )
     *                 ),
     *                 @OA\Property(property="voucher", type="object",
     *                     @OA\Property(property="code", type="string", example="DISCOUNT10")
     *                 ),
     *                 @OA\Property(property="transaction", type="object",
     *                     @OA\Property(property="amount", type="number", format="float", example=39.98),
     *                     @OA\Property(property="status", type="string", example="Success")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy đơn hàng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy đơn hàng"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi tìm kiếm đơn hàng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi trong quá trình tìm kiếm đơn hàng"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="error", type="string", example="Error details")
     *             )
     *         )
     *     )
     * )
     */
    public function print($id)
    {
        try {
            $order = Order::with(['user', 'status', 'orderDetails.product', 'voucher', 'transaction'])->find($id);

            if (!$order) {
                return $this->sendError('Không tìm thấy đơn hàng', [], 404);
            }

            return $this->sendResponse($order, 'Đơn hàng tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm đơn hàng', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/orders/confirm/{id}",
     *     summary="Duyệt đơn hàng",
     *     description="Xác nhận và duyệt đơn hàng. Thay đổi trạng thái đơn hàng từ 'Chờ duyệt' sang 'Đã duyệt'.",
     *     tags={"admin/orders"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của đơn hàng cần duyệt",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Duyệt đơn hàng thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Duyệt đơn hàng thành công"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="ORD123"),
     *                 @OA\Property(property="status_id", type="integer", example=3),
     *                 @OA\Property(property="user_id", type="integer", example=123),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 ),
     *                 @OA\Property(property="status", type="object",
     *                     @OA\Property(property="name", type="string", example="Confirmed")
     *                 ),
     *                 @OA\Property(property="orderDetails", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="product", type="object",
     *                             @OA\Property(property="name", type="string", example="Product Name")
     *                         ),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", format="float", example=19.99)
     *                     )
     *                 ),
     *                 @OA\Property(property="voucher", type="object",
     *                     @OA\Property(property="code", type="string", example="DISCOUNT10")
     *                 ),
     *                 @OA\Property(property="transaction", type="object",
     *                     @OA\Property(property="amount", type="number", format="float", example=39.98),
     *                     @OA\Property(property="status", type="string", example="Success")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy đơn hàng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy đơn hàng"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi duyệt đơn hàng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi trong quá trình duyệt đơn hàng"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="error", type="string", example="Error details")
     *             )
     *         )
     *     )
     * )
     */
    public function confirm($id)
    {
        try {
            $order = Order::with(['user', 'status', 'orderDetails.product', 'voucher', 'transaction'])->findOrFail($id);

            if (!$order) {
                return $this->sendError('Không tìm thấy đơn hàng', [], 404);
            }

            if ($order->status_id == 2) {
                $order->status_id = 3;
                $order->save();
            }

            return $this->sendResponse($order, 'Duyệt đơn hàng thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình duyệt đơn hàng', ['error' => $th->getMessage()], 500);
        }
    }
}
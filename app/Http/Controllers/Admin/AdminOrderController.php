<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            $status = $request->input('status'); // nhận id của status

            // Khởi tạo truy vấn và gọi các quan hệ liên quan
            $query = Order::withTrashed()->with(['user', 'status', 'voucher', 'orderDetails', 'transaction']);

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

            $orders = Order::withTrashed()->with(['user', 'status', 'voucher', 'orderDetails', 'transaction'])->get();

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
            $order = Order::withTrashed()->with(['user', 'status', 'orderDetails.product', 'voucher', 'transaction'])->find($id);

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
     *         required=true,
     *         description="ID của đơn hàng cần duyệt",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Duyệt đơn hàng thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Duyệt đơn hàng thành công"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="status_id", type="integer", example=3),
     *                 @OA\Property(property="total", type="number", format="float", example=150.75),
     *                 @OA\Property(property="voucher_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T12:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T12:00:00"),
     *                 @OA\Property(property="status", type="object", 
     *                     @OA\Property(property="text_status", type="string", example="Đã duyệt")
     *                 ),
     *                 @OA\Property(
     *                     property="orderDetails",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="product", type="object", 
     *                             @OA\Property(property="name", type="string", example="Sản phẩm 1")
     *                         ),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", format="float", example=75.50)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Trạng thái đơn hàng không hợp lệ hoặc đơn hàng không thể duyệt",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đơn hàng chưa thanh toán")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy đơn hàng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy đơn hàng")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi duyệt đơn hàng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi trong quá trình duyệt đơn hàng")
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
            } else if ($order->status->text_status == 'Chưa thanh toán') {
                return $this->sendError('Đơn hàng chưa thanh toán', [], 400);
            } else if ($order->status->text_status == 'Đã giao') {
                return $this->sendError('Đơn đã giao', [], 400);
            } else if ($order->status->text_status == 'Đã hủy') {
                return $this->sendError('Đơn hàng đã hủy', [], 400);
            } else if ($order->status->text_status == 'Trả hàng') {
                return $this->sendError('Đơn hàng đã trả hàng', [], 400);
            }

            if ($order->status_id == 2) {
                $order->status_id = 3;
                $order->save();
            } else if ($order->status_id == 3) {
                $order->status_id = 4;
                $order->save();
            }

            return $this->sendResponse($order, 'Duyệt đơn hàng thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình duyệt đơn hàng', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/orders/cancel/{id}",
     *     summary="Hủy đơn hàng",
     *     description="Hủy đơn hàng dựa trên ID đơn hàng và cập nhật lại số lượng sản phẩm trong kho",
     *     tags={"admin/orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của đơn hàng",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hủy đơn hàng thành công và đã cộng lại sản phẩm vào kho",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", description="ID của đơn hàng"),
     *                 @OA\Property(property="status_id", type="integer", description="Trạng thái của đơn hàng"),
     *                 @OA\Property(property="user", type="object", description="Thông tin người dùng"),
     *                 @OA\Property(property="orderDetails", type="array", @OA\Items(type="object", description="Chi tiết đơn hàng")),
     *                 @OA\Property(property="transaction", type="object", description="Thông tin giao dịch")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Lỗi: Trạng thái đơn hàng không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Đơn hàng đang giao không được phép hủy")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lỗi: Không tìm thấy đơn hàng",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Không tìm thấy đơn hàng")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi máy chủ",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi trong quá trình hủy đơn hàng")
     *         )
     *     )
     * )
     */
    public function cancel($id)
    {
        try {
            $order = Order::with(['user', 'status', 'orderDetails.product', 'transaction'])->findOrFail($id);

            if (!$order) {
                return $this->sendError('Không tìm thấy đơn hàng', [], 404);
            }

            // Kiểm tra giá trị status_id của đơn hàng
            if ($order->status_id == 3) {
                return $this->sendError('Đơn hàng đang giao không được phép hủy', [], 400);
            } else if ($order->status_id == 4) {
                return $this->sendError('Đơn hàng đã giao thành công, không được phép hủy', [], 400);
            } else if ($order->status_id == 5) {
                return $this->sendError('Đơn hàng đã được hủy trước đó', [], 400);
            } else if ($order->status_id == 6) { // Trường hợp trả hàng
                return $this->sendError('Đơn hàng đã trả về, không thể hủy', [], 400);
            }

            // Cập nhật trạng thái đơn hàng thành "Đã hủy"
            $order->status_id = 5;
            $order->save();

            // Cộng lại số lượng sản phẩm trong kho
            foreach ($order->orderDetails as $detail) {
                $product = $detail->product;
                if ($product) {
                    $product->quantity += $detail->quantity;
                    $product->save();
                }
            }

            // Ghi log admin hủy đơn
            Log::info('Admin đã hủy đơn hàng và hoàn kho', [
                'order_id' => $order->id,
                'admin_id' => auth()->id(),
            ]);

            return $this->sendResponse($order, 'Hủy đơn hàng thành công và đã cộng lại sản phẩm vào kho');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình hủy đơn hàng', ['error' => $th->getMessage()], 500);
        }
    }
}

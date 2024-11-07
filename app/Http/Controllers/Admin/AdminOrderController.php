<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends BaseController
{
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

    public function confirm($id){
        try {
            $order = Order::with(['user', 'status', 'orderDetails.product', 'voucher', 'transaction'])->findOrFail($id);

            if(!$order){
                return $this->sendError('Không tìm thấy đơn hàng', [], 404);
            }

            if ($order->status_id == 2) {
                $order->status_id = 3;
                $order->save(); 
            }

            return $this->sendResponse($order, 'Duyệt đơn hàng thành công');
        }catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình duyệt đơn hàng', ['error' => $th->getMessage()], 500);
        }
    }
}

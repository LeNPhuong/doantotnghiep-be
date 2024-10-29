<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends BaseController
{

    public function getOrders()
    {
        // Lấy danh sách đơn hàng của người dùng, bao gồm thông tin về status
        $orders = Order::with(['status', 'orderDetails.product'])->get();
        // Kiểm tra nếu không có đơn hàng nào
        if ($orders->isEmpty()) {
            return $this->sendError('Không có đơn hàng nào!', '', 404);
        }

        return $this->sendResponse($orders, 'Lấy danh sách đơn hàng thành công.');
    }
    public function getOrderDetails($orderId)
    {
        $orderDetails = OrderDetail::with('product')
            ->where('order_id', $orderId)
            ->get();

        if ($orderDetails->isEmpty()) {
            return $this->sendError('Đơn hàng không tồn tại hoặc không có chi tiết!', '', 404);
        }

        return $this->sendResponse($orderDetails, 'Chi tiết đơn hàng.');
    }

    public function checkout(Request $request)
    {
        DB::beginTransaction(); // Bắt đầu transaction để đảm bảo dữ liệu nhất quán
        try {
            $cart = $request->cart;

            if (!$cart || count($cart) === 0) {
                return $this->sendError('Giỏ hàng trống!');
            }

            // Lấy thông tin người dùng hiện tại
            $user = User::with('vouchers')->find(auth()->user()->id);

            // Lấy voucher từ request nếu có
            $voucherId = $request->voucher_id;
            $voucher = null;

            if ($voucherId) {
                // Kiểm tra xem voucher có thuộc sở hữu của người dùng không
                $voucher = $user->vouchers()->where('vouchers.id', $voucherId)->first();
                if (!$voucher || !$voucher->active || now()->lt($voucher->start_date) || now()->gt($voucher->end_date)) {
                    return $this->sendError('Voucher không hợp lệ hoặc đã hết hạn!', '', 400);
                }
            }

            // Lấy danh sách product_id từ giỏ hàng
            $productIds = array_column($cart, 'id');
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            // Kiểm tra xem có đơn hàng nào đang chờ thanh toán không
            $existingOrder = Order::where('user_id', $user->id)
                ->where('status_id', 1) // Trạng thái "đang chờ thanh toán"
                ->first();

            if ($existingOrder) {
                return $this->sendResponse($existingOrder, 'Bạn đã có một đơn hàng chưa hoàn thành. Bạn có thể thanh toán cho đơn hàng này.');
            }

            // Tính toán tổng số tiền
            $totalAmount = array_sum(array_map(function ($item) {
                return $item['price'] * $item['quantity'];
            }, $cart));

            // Áp dụng chiết khấu nếu có voucher hợp lệ
            if ($voucher) {
                $discount = $voucher->discount_type === 'percentage'
                    ? ($totalAmount * $voucher->discount_value) / 100
                    : $voucher->discount_value;

                $discount = min($discount, $voucher->max_discount_value); // Không vượt quá giá trị tối đa
                $totalAmount -= $discount; // Giảm tổng số tiền

                // Xóa voucher sau khi sử dụng
                $user->vouchers()->detach($voucher->id);
            }

            // Tạo đơn hàng
            $order = new Order();
            $order->user_id = $user->id;
            $order->code = $this->generateOrderCode();
            $order->voucher_id = $voucherId;
            $order->total_price = $totalAmount;
            $order->status_id = 1; // Trạng thái "đang chờ thanh toán"
            $order->save();

            // Lưu từng sản phẩm vào bảng order_items
            foreach ($cart as $item) {
                $product = $products->get($item['id']);

                if ($product && $product->quantity >= $item['quantity']) {
                    $product->quantity -= $item['quantity'];
                    $product->save();

                    Cache::forget('active_products');

                    OrderDetail::create([
                        'order_id' => $order->id,
                        'product_id' => $item['id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'unit' => $item['unit'],
                    ]);
                } else {
                    DB::rollBack();
                    return $this->sendError("Sản phẩm {$product->name} không đủ hàng trong kho!", '', 400);
                }
            }

            DB::commit();
            return $this->sendResponse($order, 'Đặt hàng thành công!');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError('Lỗi định dạng.', ['error' => $th->getMessage()], 404);
        }
    }

    public function infoCheckout($orderId)
    {
        // Lấy danh sách đơn hàng của người dùng, bao gồm thông tin về status
        $orders = Order::with(['status', 'orderDetails.product', 'user.addresses', 'voucher'])->where('id', $orderId)
            ->get();;
        // Kiểm tra nếu không có đơn hàng nào
        if ($orders->isEmpty()) {
            return $this->sendError('Không có đơn hàng nào!', '', 404);
        }

        return $this->sendResponse($orders, 'Lấy danh sách đơn hàng thành công.');
    }
    public function getOrderByCode(Request $request)
    {
        $order = Order::with(['status', 'orderDetails.product', 'user.addresses', 'voucher'])->where('code', $request->code)->first();

        if (!$order) {
            return $this->sendError('Không tìm thấy đơn hàng.', '', 404);
        }

        return $this->sendResponse($order, 'Đơn hàng đã được tìm thấy.');
    }

    protected function generateOrderCode()
    {
        return 'ORD-' . strtoupper(uniqid()); // Tạo mã đơn hàng duy nhất
    }

    public function cancelOrder(Request $request, $orderId)
    {
        // Lấy đơn hàng và kiểm tra xem đơn hàng có thuộc về người dùng hiện tại không
        $order = Order::where('id', $orderId)->where('user_id', auth()->user()->id)->first();

        if (!$order) {
            return $this->sendError('Không tìm thấy đơn hàng hoặc bạn không có quyền hủy đơn hàng này.', [], 404);
        }

        // Kiểm tra yêu cầu đầu vào, đảm bảo 'cancellation_reason' không được để trống
        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Lỗi định dạng', $validator->errors());
        }

        // Lấy đơn hàng
        $order = Order::findOrFail($orderId);

        // Kiểm tra trạng thái đơn hàng có thể hủy không
        if ($order->status->id === 4) {
            return $this->sendError('Đơn hàng đã hoàn thành và không thể hủy.');
        }

        // Cập nhật trạng thái và lưu lý do hủy
        $order->status_id = 5;
        $order->cancellation_reason = $request->cancellation_reason;
        $order->save();

        return $this->sendResponse($order, 'Đơn hàng đã được hủy thành công.');
    }
}

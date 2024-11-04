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

    /**
     * @OA\Get(
     *     path="/api/auth/get-orders",
     *     summary="Lấy danh sách đơn hàng của người dùng",
     *     description="Trả về danh sách đơn hàng bao gồm thông tin trạng thái và chi tiết sản phẩm.",
     *     operationId="getOrders",
     *     tags={"auth"},
     *     security={{"bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh sách đơn hàng thành công.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="status", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Đang xử lý")
     *                     ),
     *                     @OA\Property(property="orderDetails", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="quantity", type="integer", example=2),
     *                             @OA\Property(property="product", type="object",
     *                                 @OA\Property(property="id", type="integer", example=10),
     *                                 @OA\Property(property="name", type="string", example="Sản phẩm A"),
     *                                 @OA\Property(property="price", type="number", format="float", example=150.5)
     *                             )
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Lấy danh sách đơn hàng thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không có đơn hàng nào!",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không có đơn hàng nào!")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/auth/orders/{orderId}/details",
     *     summary="Lấy chi tiết đơn hàng",
     *     operationId="getOrderDetails",
     *     description="Trả về chi tiết của đơn hàng theo ID, bao gồm thông tin sản phẩm.",
     *     tags={"auth"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="ID của đơn hàng cần lấy chi tiết.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chi tiết đơn hàng thành công.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="quantity", type="integer", example=2),
     *                     @OA\Property(property="product", type="object",
     *                         @OA\Property(property="id", type="integer", example=10),
     *                         @OA\Property(property="name", type="string", example="Sản phẩm A"),
     *                         @OA\Property(property="price", type="number", format="float", example=150.5)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Chi tiết đơn hàng.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Đơn hàng không tồn tại hoặc không có chi tiết!",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đơn hàng không tồn tại hoặc không có chi tiết!")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/checkout",
     *     summary="Xử lý checkout đơn hàng",
     *     operationId="checkout",
     *     description="Tạo đơn hàng mới từ giỏ hàng của người dùng",
     *     security={{"bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cart"},
     *             @OA\Property(
     *                 property="cart",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1, description="ID của sản phẩm"),
     *                     @OA\Property(property="quantity", type="integer", example=2, description="Số lượng sản phẩm"),
     *                     @OA\Property(property="price", type="number", format="float", example=100, description="Giá sản phẩm"),
     *                     @OA\Property(property="unit", type="string", example="1kg", description="Đơn vị sản phẩm")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="voucher_id",
     *                 type="integer",
     *                 example=1,
     *                 description="ID của voucher (nếu có)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đặt hàng thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", description="Thông tin đơn hàng",
     *                 @OA\Property(property="id", type="integer", description="ID của đơn hàng"),
     *                 @OA\Property(property="code", type="string", description="Mã đơn hàng"),
     *                 @OA\Property(property="total_price", type="number", format="float", description="Tổng giá trị đơn hàng"),
     *                 @OA\Property(property="status_id", type="integer", description="Trạng thái đơn hàng"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", description="Thời gian tạo đơn hàng"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", description="Thời gian cập nhật đơn hàng")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Lỗi định dạng hoặc dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Thông báo lỗi"),
     *             @OA\Property(property="errors", type="object", description="Chi tiết lỗi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lỗi không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Thông báo lỗi")
     *         )
     *     )
     * )
     */
    public function checkout(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'cart' => 'required|array',
            'cart.*.id' => 'required|integer|exists:product,id', // Kiểm tra từng sản phẩm trong giỏ hàng
            'cart.*.quantity' => 'required|integer|min:1', // Kiểm tra số lượng sản phẩm
            'cart.*.unit' => 'required|string|exists:units,id', // Kiểm tra đơn vị sản phẩm
            'voucher_id' => 'nullable|integer|exists:vouchers,id', // Kiểm tra voucher nếu có
        ]);

        // Nếu có lỗi xác thực, trả về thông báo lỗi
        if ($validator->fails()) {
            return $this->sendError('Lỗi định dạng', $validator->errors(), 400);
        }
        // Bắt đầu transaction để đảm bảo dữ liệu nhất quán
        DB::beginTransaction();
        try {
            // Lấy giỏ hàng từ request
            $cart = $request->cart;

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
                // Nếu có đơn hàng chờ thanh toán, trả về thông tin đơn hàng
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

                // Kiểm tra số lượng sản phẩm trong kho
                if ($product && $product->quantity >= $item['quantity']) {
                    $product->quantity -= $item['quantity'];
                    $product->save();

                    // Xóa cache cho sản phẩm đang hoạt động
                    Cache::forget('active_products');
                    Cache::forget("product_detail_{$product->id}");

                    // Lưu thông tin chi tiết đơn hàng
                    OrderDetail::create([
                        'order_id' => $order->id,
                        'product_id' => $item['id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'unit' => $item['unit'],
                    ]);
                } else {
                    // Nếu không đủ hàng trong kho, rollback và trả về lỗi
                    DB::rollBack();
                    return $this->sendError("Sản phẩm {$product->name} không đủ hàng trong kho!", '', 400);
                }
            }

            // Commit transaction nếu tất cả đều thành công
            DB::commit();
            return $this->sendResponse($order, 'Đặt hàng thành công!');
        } catch (\Throwable $th) {
            // Rollback nếu có lỗi xảy ra
            DB::rollBack();
            return $this->sendError('Lỗi định dạng.', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/info-checkout/{orderId}",
     *     summary="Lấy thông tin đơn hàng vừa checkout để thanh toán",
     *     operationId="infoCheckout",
     *     description="Trả về thông tin chi tiết của đơn hàng theo ID",
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         ),
     *         description="ID của đơn hàng cần lấy thông tin"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thông tin đơn hàng thành công",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", description="ID của đơn hàng"),
     *                 @OA\Property(property="code", type="string", description="Mã đơn hàng"),
     *                 @OA\Property(property="total_price", type="number", format="float", description="Tổng giá trị đơn hàng"),
     *                 @OA\Property(property="status", type="object", description="Trạng thái đơn hàng",
     *                     @OA\Property(property="id", type="integer", description="ID trạng thái"),
     *                     @OA\Property(property="name", type="string", description="Tên trạng thái")
     *                 ),
     *                 @OA\Property(property="orderDetails", type="array", description="Chi tiết sản phẩm trong đơn hàng",
     *                     @OA\Items(
     *                         @OA\Property(property="product", type="object", description="Thông tin sản phẩm",
     *                             @OA\Property(property="id", type="integer", description="ID sản phẩm"),
     *                             @OA\Property(property="name", type="string", description="Tên sản phẩm"),
     *                             @OA\Property(property="price", type="number", format="float", description="Giá sản phẩm"),
     *                             @OA\Property(property="unit", type="string", description="Đơn vị sản phẩm")
     *                         ),
     *                         @OA\Property(property="quantity", type="integer", description="Số lượng sản phẩm trong đơn hàng")
     *                     )
     *                 ),
     *                 @OA\Property(property="user", type="object", description="Thông tin người dùng",
     *                     @OA\Property(property="id", type="integer", description="ID người dùng"),
     *                     @OA\Property(property="name", type="string", description="Tên người dùng"),
     *                     @OA\Property(property="addresses", type="array", description="Danh sách địa chỉ của người dùng",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer", description="ID địa chỉ"),
     *                             @OA\Property(property="address", type="string", description="Địa chỉ")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="voucher", type="object", description="Thông tin voucher (nếu có)",
     *                     @OA\Property(property="id", type="integer", description="ID voucher"),
     *                     @OA\Property(property="code", type="string", description="Mã voucher"),
     *                     @OA\Property(property="discount_value", type="number", format="float", description="Giá trị chiết khấu của voucher")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy đơn hàng",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Thông báo lỗi")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/orders/code",
     *     summary="Lấy đơn hàng theo mã",
     *     description="Tìm kiếm đơn hàng theo mã code",
     *     operationId="getOrderByCode",
     *     security={{"bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string", example="ORD123456", description="Mã đơn hàng cần tìm.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đơn hàng đã được tìm thấy.",
     *         @OA\JsonContent(
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Lỗi định dạng.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lỗi định dạng"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy đơn hàng.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Không tìm thấy đơn hàng.")
     *         )
     *     )
     * )
     */
    public function getOrderByCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:255', // Yêu cầu mã đơn hàng là bắt buộc và phải là chuỗi
        ]);

        if ($validator->fails()) {
            return $this->sendError('Lỗi định dạng', $validator->errors(), 400);
        }

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

    /**
     * @OA\Delete(
     *     path="/api/orders/{orderId}/cancel",
     *     summary="Hủy đơn hàng",
     *     description="Xóa đơn hàng bằng id của order",
     *     operationId="cancelOrder",
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="ID của đơn hàng cần hủy",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="cancellation_reason", type="string", example="Đơn hàng không còn cần thiết")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đơn hàng đã được hủy thành công.",
     *         @OA\JsonContent(
     *            
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Lỗi định dạng.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lỗi định dạng"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy đơn hàng hoặc không có quyền hủy đơn hàng.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Không tìm thấy đơn hàng hoặc bạn không có quyền hủy đơn hàng này.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Không thể hủy đơn hàng đã hoàn thành.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Đơn hàng đã hoàn thành và không thể hủy.")
     *         )
     *     )
     * )
     */
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

        // Kiểm tra trạng thái đơn hàng có thể hủy không (trạng thái phải lớn hơn 2)
        if ($order->status->id <= 2) {
            return $this->sendError('Đơn hàng không thể hủy vì trạng thái của nó không hợp lệ.');
        }

        // Kiểm tra trạng thái đơn hàng đã hoàn thành không
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

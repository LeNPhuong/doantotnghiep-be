<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/payment",
     *     summary="Xử lý thanh toán cho đơn hàng",
     *     description="Phương thức này cho phép người dùng xử lý thanh toán cho đơn hàng đang chờ thanh toán. Người dùng có thể chọn phương thức thanh toán và sử dụng voucher nếu có. Nếu thanh toán thành công, thông tin giao dịch sẽ được lưu trữ.",
     *     tags={"payment"},
     *     security={{"bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Thông tin cần thiết để xử lý thanh toán",
     *         @OA\JsonContent(
     *             required={"payment_method"},
     *             @OA\Property(property="payment_method", type="string", example="momo", description="Phương thức thanh toán: ví dụ như momo, COD (thanh toán khi nhận hàng), hoặc các phương thức khác."),
     *             @OA\Property(property="voucher_id", type="integer", example=1, description="ID của voucher (nếu có) để áp dụng chiết khấu."),
     *             @OA\Property(property="note", type="string", example="Ghi chú thanh toán", description="Ghi chú tùy chọn từ người dùng cho giao dịch này."),
     *             @OA\Property(property="name", type="string", example="Nguyễn Văn A", description="Tên của người nhận hàng."),
     *             @OA\Property(property="phone", type="string", example="0901234567", description="Số điện thoại liên hệ của người nhận hàng."),
     *             @OA\Property(property="email", type="string", example="email@example.com", description="Địa chỉ email của người nhận hàng."),
     *             @OA\Property(property="address", type="string", example="123 Đường ABC, Quận 1, TP. HCM", description="Địa chỉ giao hàng của người nhận.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thanh toán thành công.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Thanh toán thành công."),
     *             @OA\Property(property="data", type="object", 
     *                 @OA\Property(property="id", type="integer", example=1, description="ID của giao dịch."),
     *                 @OA\Property(property="user_id", type="integer", example=1, description="ID của người dùng."),
     *                 @OA\Property(property="order_id", type="integer", example=1, description="ID của đơn hàng."),
     *                 @OA\Property(property="total_price", type="number", format="float", example=150000, description="Tổng giá trị của giao dịch."),
     *                 @OA\Property(property="note", type="string", example="Ghi chú thanh toán", description="Ghi chú từ người dùng."),
     *                 @OA\Property(property="name", type="string", example="Nguyễn Văn A", description="Tên của người nhận hàng."),
     *                 @OA\Property(property="phone", type="string", example="0901234567", description="Số điện thoại liên hệ."),
     *                 @OA\Property(property="email", type="string", example="email@example.com", description="Địa chỉ email."),
     *                 @OA\Property(property="address", type="string", example="123 Đường ABC, Quận 1, TP. HCM", description="Địa chỉ giao hàng."),
     *                 @OA\Property(property="payment_method", type="string", example="momo", description="Phương thức thanh toán được sử dụng."),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-04T12:00:00Z", description="Thời gian tạo giao dịch."),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-04T12:00:00Z", description="Thời gian cập nhật giao dịch.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Lỗi định dạng hoặc voucher không hợp lệ.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Voucher không hợp lệ hoặc đã hết hạn!"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="code", type="integer", example=400)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không có đơn hàng nào đang chờ thanh toán.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Không có đơn hàng nào đang chờ thanh toán."),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="code", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi xảy ra trong quá trình thanh toán.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Thanh toán thất bại. Vui lòng thử lại."),
     *             @OA\Property(property="error", type="string", example="Lỗi chi tiết")
     *         )
     *     )
     * )
     */
    public function processPayment(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:momo,cod', // Kiểm tra phương thức thanh toán
            'voucher_id' => 'nullable|exists:vouchers,id', // Kiểm tra voucher nếu có
            'name' => 'required|string|max:255', // Kiểm tra tên
            'phone' => 'required|string|max:15', // Kiểm tra số điện thoại
            'email' => 'required|email|max:255', // Kiểm tra email
            'address' => 'required|string|max:255', // Kiểm tra địa chỉ
            'note' => 'nullable|string|max:255', // Kiểm tra ghi chú
        ]);

        if ($validator->fails()) {
            return $this->sendError('Lỗi định dạng', $validator->errors(), 400);
        }
        // Lấy ID của người dùng hiện tại
        $userId = auth()->user()->id;
        // Lấy thông tin đơn hàng sau khi checkout
        $order = Order::where('user_id', $userId)
            ->where('status_id', 1) // Giả sử '1' là trạng thái "pending"
            ->first();

        if (!$order) {
            return $this->sendError('Không có đơn hàng nào đang chờ thanh toán.', '', 404);
        }

        // Lấy phương thức thanh toán từ request
        $paymentMethod = $request->input('payment_method'); // Ví dụ: 'momo', 'cod'

        // Số tiền thanh toán
        $amount = $order->total_price; // Tổng giá trị đơn hàng

        // Xử lý thanh toán và lưu transaction
        try {
            // Kiểm tra voucher từ request
            $voucherId = $request->input('voucher_id');

            if ($voucherId && is_null($order->voucher_id)) {
                // Lấy thông tin người dùng và voucher
                $user = User::with('vouchers')->find($userId);
                $voucher = $user->vouchers()->find($voucherId);

                // Xác minh tính hợp lệ của voucher
                if ($voucher && $voucher->active && now()->between($voucher->start_date, $voucher->end_date)) {
                    // Tính toán chiết khấu
                    $discount = 0;
                    if ($voucher->discount_type === 'percentage') {
                        $discount = ($amount * $voucher->discount_value) / 100;
                    } else {
                        $discount = $voucher->discount_value;
                    }
                    $discount = min($discount, $voucher->max_discount_value);
                    $amount -= $discount;

                    // Cập nhật thông tin voucher vào đơn hàng
                    $order->voucher_id = $voucherId;
                    $order->total_price = $amount; // Cập nhật tổng giá trị đơn hàng
                    $user->vouchers()->detach($voucherId); // Xóa voucher khỏi người dùng
                    $order->save();
                } elseif ($voucherId) {
                    return $this->sendError('Voucher không hợp lệ hoặc đã hết hạn!', '', 400);
                }
            }

            // Gọi hàm xử lý thanh toán, ví dụ processCreditCardPayment()
            if ($this->handlePayment($order, $amount, $userId, $request->all(), $paymentMethod)) {
                return $this->handlePayment($order, $amount, $userId, $request->all(), $paymentMethod);
            }
            // Lưu thông tin transaction
            $transaction = Transaction::create([
                'user_id' => $userId,
                'order_id' => $order->id,
                'total_price' => $amount,
                'note' => $request->input('note', ''), // Lưu ý từ request, nếu có
                'name' => $request->input('name', ''),
                'phone' => $request->input('phone', ''),
                'email' => $request->input('email', ''),
                'address' => $request->input('address', ''),
                'payment_method' => $paymentMethod,
            ]);

            // Cập nhật trạng thái đơn hàng
            $order->status_id = 2; // Giả sử '2' là trạng thái "paid"
            $order->save();

            return $this->sendResponse($transaction, 'Thanh toán thành công.');
        } catch (\Exception $e) {
            // Xử lý khi thanh toán thất bại
            return $this->sendError('Thanh toán thất bại. Vui lòng thử lại.', $e->getMessage(), 500);
        }
    }

    // Hàm xử lý thanh toán
    protected function handlePayment($order, $amount, $userId, $data, $paymentMethod)
    {
        // Tùy theo phương thức thanh toán, bạn có thể gọi các API tương ứng
        if ($paymentMethod == 'momo') {
            return $this->processMoMoPayment($order, $amount, $userId, $data);
        } elseif ($paymentMethod == 'cod') {
            // Thanh toán khi nhận hàng (COD), không cần xử lý thêm
            return;
        } else {
            throw new \Exception('Phương thức thanh toán không hợp lệ.');
        }
    }

    // Xử lý thanh toán bằng MoMo
    // Cập nhật phương thức processMoMoPayment để trả về JSON thay vì điều hướng
    public function processMoMoPayment($order, $amount1, $userId, $data)
    {
        // Đọc thông tin từ request
        $endpoint = 'https://test-payment.momo.vn/v2/gateway/api/create';
        $accessKey = 'F8BBA842ECF85';
        $secretKey = 'K951B6PE1waDMi640xX08PD3vg6EkVlz';
        $partnerCode = 'MOMO';
        $orderInfo = 'thanh toán bằng momo';
        $redirectUrl = 'https://webhook.site/b3088a6a-2d17-4f8d-a383-71389a6c600b';
        $ipnUrl = 'http://su0hfhr1j2enqztuyky6pp.webrelay.io/api/test';
        $requestType = 'payWithMethod';
        $orderId = time() . "";
        $requestId = time() . "";
        $extraData = json_encode(['userId' => $userId, 'order' => $order, 'data' => $data]);;

        // Lấy các thông tin thanh toán từ request
        // $amount = $request->input('amount', '50000'); // số tiền thanh toán
        $amount =  $amount1; // số tiền thanh toán
        $partnerName = 'MoMo Payment';
        $storeId = 'Test Store';
        $autoCapture = true;
        $lang = 'vi';

        // Chuỗi raw hash để tạo signature
        $rawHash = "accessKey=$accessKey&amount=$amount&extraData=$extraData&ipnUrl=$ipnUrl&orderId=$orderId&orderInfo=$orderInfo&partnerCode=$partnerCode&redirectUrl=$redirectUrl&requestId=$requestId&requestType=$requestType";
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        // Tạo data gửi đi
        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => $partnerName,
            'storeId' => $storeId,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'requestType' => $requestType,
            'ipnUrl' => $ipnUrl,
            'lang' => $lang,
            'redirectUrl' => $redirectUrl,
            'autoCapture' => $autoCapture,
            'extraData' => $extraData,
            'signature' => $signature,
        ];

        // Gửi yêu cầu POST tới MoMo
        $response = Http::post($endpoint, $data);
        // Kiểm tra phản hồi
        if ($response->successful() && isset($response['payUrl'])) {
            return response()->json([
                'status' => 'success',
                'payUrl' => $response['payUrl']
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Thanh toán MoMo thất bại.',
                'response' => $response->json() // Ghi lại thông tin phản hồi để kiểm tra
            ], 500);
        }
    }

    public function test(Request $request)
    {
        Log::info('Function test called');
        Log::info($request->all());

        // Lấy dữ liệu trả về từ MoMo
        $responseData = $request->all();

        // Kiểm tra xem extraData có tồn tại không
        if (isset($responseData['extraData'])) {
            // Giải mã JSON của extraData
            $extraData = json_decode($responseData['extraData'], true);

            // Lấy từng phần tử từ extraData nếu chúng tồn tại
            $userId = $extraData['userId'] ?? null;

            // Lấy thông tin order nếu tồn tại
            $order = $extraData['order'] ?? null;
            if ($order) {
                $orderId = $order['id'] ?? null;
                $orderTotalPrice = $order['total_price'] ?? null;

                // Lấy dữ liệu trong data
                $dataPaymenMethod = $extraData['data']['payment_method'] ?? [];
                $dataNote = $extraData['data']['note'] ?? [];
                $dataName = $extraData['data']['name'] ?? [];
                $dataPhone = $extraData['data']['phone'] ?? [];
                $dataEmail = $extraData['data']['email'] ?? [];
                $dataAddress = $extraData['data']['address'] ?? [];
            }

            $order = Order::where('user_id',  $userId)
                ->where('status_id', 1) // Giả sử '1' là trạng thái "pending"
                ->first();
            // Lưu thông tin transaction
            $transaction = Transaction::create([
                'user_id' => $userId,
                'order_id' => $orderId,
                'total_price' => $orderTotalPrice,
                'note' => $dataNote, // Lưu ý từ request, nếu có
                'name' => $dataName,
                'phone' => $dataPhone,
                'email' => $dataEmail,
                'address' => $dataAddress,
                'payment_method' => $dataPaymenMethod,
            ]);

            // Cập nhật trạng thái đơn hàng
            $order->status_id = 2; // Giả sử '2' là trạng thái "paid"
            $order->save();

            return $this->sendResponse($transaction, 'Thanh toán thành công.');
        }
    }
}

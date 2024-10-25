<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    public function processPayment(Request $request)
    {
        // Lấy ID của người dùng hiện tại
        $userId = auth()->id();

        // Lấy thông tin đơn hàng sau khi checkout
        $order = Order::where('user_id', $userId)
            ->where('status_id', 1) // Giả sử '1' là trạng thái "pending"
            ->first();

        if (!$order) {
            return $this->sendError('Không có đơn hàng nào đang chờ thanh toán.', '', 404);
        }

        // Lấy phương thức thanh toán từ request
        $paymentMethod = $request->input('payment_method'); // Ví dụ: 'credit_card', 'paypal', 'cod'

        // Số tiền thanh toán
        $amount = $order->total_price; // Tổng giá trị đơn hàng

        // Xử lý thanh toán và lưu transaction
        try {
            // Gọi hàm xử lý thanh toán, ví dụ processCreditCardPayment()
            $transactionId = $this->handlePayment($order, $paymentMethod, $amount);

            // Lưu thông tin transaction
            $transaction = Transaction::create([
                'user_id' => $userId,
                'order_id' => $order->id,
                'total_price' => $amount,
                'note' => $request->input('note', ''), // Lưu ý từ request, nếu có
                'name' => $request->input('name', ''),
                'phone' => $request->input('phone', ''),
                'email' => $request->input('email', ''),
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
    protected function handlePayment($order, $paymentMethod, $amount)
    {
        // Tùy theo phương thức thanh toán, bạn có thể gọi các API tương ứng
        if ($paymentMethod == 'credit_card') {
            return $this->processCreditCardPayment($order, $amount);
        } elseif ($paymentMethod == 'paypal') {
            return $this->processPayPalPayment($order, $amount);
        } elseif ($paymentMethod == 'cod') {
            // Thanh toán khi nhận hàng (COD), không cần xử lý thêm
            return null;
        } else {
            throw new \Exception('Phương thức thanh toán không hợp lệ.');
        }
    }

    // Xử lý thanh toán bằng thẻ tín dụng
    protected function processCreditCardPayment($order, $amount)
    {
        // Gọi API thanh toán và trả về mã giao dịch
        return 'credit_card_transaction_id'; // Thay thế với mã giao dịch thực tế
    }

    // Xử lý thanh toán qua PayPal
    protected function processPayPalPayment($order, $amount)
    {
        // Gọi API PayPal và trả về mã giao dịch
        return 'paypal_transaction_id'; // Thay thế với mã giao dịch thực tế
    }
}

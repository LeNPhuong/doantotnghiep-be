<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán thành công</title>
</head>
<body>
    <h1>Xin chào {{ $transaction->name }},</h1>
    <p>Chúng tôi đã nhận được thanh toán cho đơn hàng của bạn.</p>
    <p><strong>Mã đơn hàng:</strong> {{ $transaction->order_id }}</p>
    <p><strong>Tổng tiền:</strong> {{ number_format($transaction->total_price, 0, ',', '.') }} VNĐ</p>
    <p>Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!</p>
</body>
</html>

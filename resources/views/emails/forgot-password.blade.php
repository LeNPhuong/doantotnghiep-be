<div
    style="border: 3px solid green; padding: 15px; background: lightgreen; width: 600px; margin: auto; border-radius: 10px; font-family: Arial, sans-serif; color: #064420;">
    <h3 style="text-align: center;">Xin chào, {{ $user->name }}</h3>

    <p>
        Chào bạn, chúng tôi rất vui khi được hỗ trợ bạn! Dưới đây là thông tin bạn cần:
    </p>

    <p style="font-size: 18px; font-weight: bold; text-align: center;">
        Mã OTP của bạn: <span style="color: #d9534f;">{{ $otp }}</span>
    </p>

    <p style="text-align: center;">
        Mã OTP này có hiệu lực trong vòng 1 phút. Vui lòng không chia sẻ mã này với bất kỳ ai để đảm bảo an toàn cho tài
        khoản của bạn.
    </p>

    <p style="text-align: right; font-style: italic;">
        Trân trọng,<br> Đội ngũ hỗ trợ
    </p>
</div>

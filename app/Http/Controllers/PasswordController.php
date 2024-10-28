<?php

namespace App\Http\Controllers;

use App\Mail\ForgotPassword;
use App\Models\User;
use App\Models\UserResetToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PasswordController extends BaseController
{
    public function sendOtp(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users|email'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Lỗi định dạng', $validator->errors());
        }
        $user = User::where('email', $request->email)->first();
        $otp = rand(100000, 999999); // Tạo mã OTP ngẫu nhiên
        $otpData = [
            'email' => $request->email,
            'token' => $otp,
        ];
        if (UserResetToken::create($otpData)) {
            Mail::to($request->email)->send(new ForgotPassword($user, $otp));
            return $this->sendResponse('OTP đã được gửi tới email', 200);
        }
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Lỗi định dạng', $validator->errors());
        }
        // Kiểm tra OTP có tồn tại và chưa hết hạn (1 phút)
        $otpData = UserResetToken::where('email', $request->email)
            ->where('token', $request->otp)
            ->first();

        if (!$otpData) {
            return $this->sendError('Mã OTP không tồn tại. Vui lòng kiểm tra lại hoặc yêu cầu mã mới.', '', 400);
        }

        if (!$otpData || Carbon::parse($otpData->created_at)->addMinute()->isPast()) {
            UserResetToken::where('email', $otpData->email)->delete();
            return $this->sendError('Mã OTP đã hết hiệu lực 1 phút', '', 400);
        }

        // Trả về thông báo xác nhận thành công nếu OTP đúng
        return $this->sendResponse('OTP đã xác nhận thành công', 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
            'password' => [
                'required',
                'min:6',
                'regex:/[a-zA-Z]/', // ít nhất một chữ cái
                'regex:/[0-9]/', // ít nhất một số
                'regex:/[^a-zA-Z0-9]/', // ít nhất một ký tự đặc biệt
            ],
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Lỗi định dạng', $validator->errors());
        }

        $otpData =  UserResetToken::where('token', $request->otp)->firstOrFail();
        // Đặt lại mật khẩu cho user
        $user = User::where('email', $otpData->email)->first();
        if (!$user) {
            return $this->sendError('Không tìm thấy user', '', 404);
        }
        $user->password = bcrypt($request->password);
        $user->save();
        // Xóa OTP sau khi đổi mật khẩu thành công
        UserResetToken::where('email', $otpData->email)->delete();

        return $this->sendResponse('Password has been reset successfully', 200);
    }
    public function changePassword(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'password' => [
                'required',
                'min:6',
                'regex:/[a-zA-Z]/', // ít nhất một chữ cái
                'regex:/[0-9]/', // ít nhất một số
                'regex:/[^a-zA-Z0-9]/', // ít nhất một ký tự đặc biệt
            ],
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Lỗi định dạng', $validator->errors());
        }
        // Kiểm tra nếu mật khẩu mới trùng với mật khẩu hiện tại
        if (password_verify($request->password, auth()->user()->password)) {
            return $this->sendError('Mật khẩu mới phải khác với mật khẩu hiện tại');
        }

        // Cập nhật mật khẩu mới
        $user = User::where('id', auth()->user()->id)->first();
        $user->password = bcrypt($request->password);
        $user->save();

        return  $this->sendResponse('Đổi mật khẩu thành công', 200);
    }
}

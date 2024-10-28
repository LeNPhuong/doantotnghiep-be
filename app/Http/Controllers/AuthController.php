<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use App\Mail\ForgotPassword;
use App\Models\User;
use App\Models\UserResetToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseController
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'required|regex:/^(\+?\d{1,3}[- ]?)?\d{10}$/',
            'email' => 'required|email|unique:users,email',
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

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['user'] = $user;
        return $this->sendResponse($success, 'Đăng ký tài khoản thành công.');
    }
    public function login()
    {
        $credentials = request(['email', 'password']);

        $user = User::where('email', $credentials['email'])->first();

        // Kiểm tra nếu người dùng không tồn tại hoặc không hoạt động
        if (!$user || !$user->active) {
            return $this->sendError('Không được chấp nhận', ['error' => 'User not found or inactive'], 401);
        }

        // Thực hiện xác thực với credentials
        if (!$token = auth()->attempt($credentials)) {
            return $this->sendError('Không được chấp nhận', ['error' => 'Unauthorized'], 401);
        }
        // Trả về token
        $success = $this->respondWithToken($token);
        return $this->sendResponse($success, 'Đăng nhập thành công');
    }
    public function refresh()
    {
        $success = $this->respondWithToken(auth()->refresh());
        return $this->sendResponse($success, 'Đã refresh thông tin tài khoản');
    }
    public function logout()
    {
        auth()->logout();
        return $this->sendResponse('', 'Đăng xuất thành công');
    }
    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60, // thời gian sống của token
        ];
    }

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
}

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
    /**
     * @OA\Post(
     *     path="/api/forgot-password/send-otp",
     *     summary="Gửi mã OTP đến email của người dùng",
     *     description="API này sẽ gửi một mã OTP ngẫu nhiên tới email của người dùng để xác nhận yêu cầu quên mật khẩu.",
     *     tags={"forget password"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", description="Email của người dùng", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP đã được gửi tới email",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP đã được gửi tới email"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Lỗi định dạng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lỗi định dạng"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email field is required."))
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/forgot-password/verify-otp",
     *     summary="Xác thực mã OTP",
     *     description="API này xác thực mã OTP mà người dùng đã nhận qua email để hoàn tất việc xác minh.",
     *     tags={"forget password"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", description="Email của người dùng", example="user@example.com"),
     *             @OA\Property(property="otp", type="string", description="Mã OTP gồm 6 chữ số", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP đã xác nhận thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP đã xác nhận thành công"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Mã OTP không tồn tại hoặc đã hết hiệu lực",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Mã OTP không tồn tại. Vui lòng kiểm tra lại hoặc yêu cầu mã mới."),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Lỗi định dạng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lỗi định dạng"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email field is required.")),
     *                 @OA\Property(property="otp", type="array", @OA\Items(type="string", example="The otp must be 6 digits."))
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/forgot-password/reset-password",
     *     summary="Đặt lại mật khẩu",
     *     description="API này đặt lại mật khẩu của người dùng bằng cách sử dụng OTP đã nhận được.",
     *     tags={"forget password"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="otp", type="string", description="Mã OTP gồm 6 chữ số", example="123456"),
     *             @OA\Property(property="password", type="string", description="Mật khẩu mới có ít nhất 6 ký tự, bao gồm chữ cái, số và ký tự đặc biệt", example="NewPassword@123"),
     *             @OA\Property(property="c_password", type="string", description="Xác nhận mật khẩu mới, phải khớp với trường password", example="NewPassword@123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đặt lại mật khẩu thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password has been reset successfully"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Mã OTP không tồn tại hoặc lỗi định dạng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lỗi định dạng"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="otp", type="array", @OA\Items(type="string", example="The otp must be 6 digits.")),
     *                 @OA\Property(property="password", type="array", @OA\Items(type="string", example="The password must be at least 6 characters.")),
     *                 @OA\Property(property="c_password", type="array", @OA\Items(type="string", example="The c_password and password must match."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy user",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy user"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
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

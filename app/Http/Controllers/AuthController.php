<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use App\Models\User;
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
}

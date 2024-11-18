<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;


class AuthController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"auth"},
     *     summary="Đăng ký người dùng mới",
     *     description="Endpoint này cho phép người dùng mới đăng ký với tên, số điện thoại, email và mật khẩu.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "phone", "email", "password", "c_password"},
     *             @OA\Property(property="name", type="string", example="Nguyễn Văn A", description="Họ và tên của người dùng"),
     *             @OA\Property(property="phone", type="string", example="0398981104", description="Số điện thoại của người dùng"),
     *             @OA\Property(property="email", type="string", example="nguyenvana@example.com", description="Địa chỉ email của người dùng"),
     *             @OA\Property(property="password", type="string", example="Password@123", description="Mật khẩu của người dùng (tối thiểu 6 ký tự, phải bao gồm chữ cái, số và ký tự đặc biệt)"),
     *             @OA\Property(property="c_password", type="string", example="Password@123", description="Xác nhận mật khẩu của người dùng")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Người dùng đã được đăng ký thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user", type="object", 
     *                 @OA\Property(property="id", type="integer", example=1, description="ID của người dùng"),
     *                 @OA\Property(property="name", type="string", example="Nguyễn Văn A"),
     *                 @OA\Property(property="phone", type="string", example="+84123456789"),
     *                 @OA\Property(property="email", type="string", example="nguyenvana@example.com")
     *             ),
     *             @OA\Property(property="message", type="string", example="Đăng ký tài khoản thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Lỗi xác thực",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Lỗi định dạng"),
     *             @OA\Property(property="validation_errors", type="object",
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="Trường tên là bắt buộc.")),
     *                 @OA\Property(property="phone", type="array", @OA\Items(type="string", example="Trường điện thoại là bắt buộc.")),
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="Email đã được sử dụng.")),
     *                 @OA\Property(property="password", type="array", @OA\Items(type="string", example="Mật khẩu phải có ít nhất 6 ký tự.")),
     *                 @OA\Property(property="c_password", type="array", @OA\Items(type="string", example="Mật khẩu xác nhận và mật khẩu phải khớp."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi máy chủ nội bộ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra, vui lòng thử lại.")
     *         )
     *     )
     * )
     */
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
            return $this->sendError('Lỗi định dạng', $validator->errors(), 400);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);

        // Kiểm tra và gán giá trị role nếu cần (Đảm bảo chỉ có 'user' role)
        if (isset($input['role']) && $input['role'] !== 'user') {
            $input['role'] = 'user'; // Đặt lại role mặc định nếu có trường role
        }
        
        $user = User::create($input);
        $success['user'] = $user;
        return $this->sendResponse($success, 'Đăng ký tài khoản thành công.', 201);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"auth"},
     *     summary="Đăng nhập",
     *     description="Phương thức này cho phép người dùng đăng nhập vào hệ thống và nhận token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="your_password"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đăng nhập thành công.",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="your_jwt_token_here"),
     *             @OA\Property(property="message", type="string", example="Đăng nhập thành công"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Không được chấp nhận.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Người dùng không tìm thấy hoặc không hoạt động.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found or inactive"),
     *         )
     *     ),
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     tags={"auth"},
     *     summary="Làm mới token",
     *     description="Phương thức này cho phép người dùng làm mới token JWT để nhận thông tin tài khoản mới.",
     *     @OA\Response(
     *         response=200,
     *         description="Refresh token thành công.",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="new_jwt_token_here"),
     *             @OA\Property(property="message", type="string", example="Đã refresh thông tin tài khoản"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Không được chấp nhận.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized"),
     *         )
     *     )
     * )
     */
    public function refresh()
    {
        try {
            // Làm mới token
            $token = auth()->refresh();
            $success = $this->respondWithToken($token);
            return $this->sendResponse($success, 'Đã refresh thông tin tài khoản');
        } catch (\Exception $e) {
            // Bắt lỗi nếu refresh không thành công
            return $this->sendError('Không được chấp nhận', ['error' => 'Unauthorized'], 401);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"auth"},
     *     summary="Đăng xuất người dùng",
     *     description="Thực hiện đăng xuất cho người dùng đã xác thực.",
     *     security={{"bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Đăng xuất thành công"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Người dùng không được phép (Unauthorized)"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi máy chủ nội bộ"
     *     ),
     * )
     */
    public function logout()
    {
        try {
            auth()->logout();
            return $this->sendResponse('', 'Đăng xuất thành công');
        } catch (\Exception $e) {
            return $this->sendError('Đã xảy ra lỗi', ['error' => 'Không thể thực hiện đăng xuất'], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/auth/google/redirect",
     *     summary="Chuyển hướng người dùng đến trang xác thực Google",
     *     tags={"Google Auth"},
     *     @OA\Response(
     *         response=302,
     *         description="Chuyển hướng đến Google để đăng nhập",
     *     ),
     * )
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Hàm callback xử lý dữ liệu từ Google
    public function handleGoogleCallback(Request $request)
    {
        try {
            // Lấy thông tin người dùng từ Google sau khi có mã code
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Kiểm tra người dùng đã tồn tại trong hệ thống chưa
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Nếu chưa có, tạo mới người dùng
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => bcrypt(Str::random(16)), // Tạo mật khẩu ngẫu nhiên
                    'google_id' => $googleUser->getId(),
                ]);
            }

            // Đăng nhập người dùng vào ứng dụng
            Auth::login($user);
            // Tạo JWT token và trả về cho người dùng
            $token = JWTAuth::fromUser($user);

            return response()->json(['token' => $token], 200);
        } catch (\Exception $e) {
            // In lỗi chi tiết để giúp debug
            return response()->json(['error' => 'Đăng nhập bằng Google thất bại!', 'message' => $e->getMessage()], 500);
        }
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

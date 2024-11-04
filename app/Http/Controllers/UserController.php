<?php

namespace App\Http\Controllers;

use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/auth/profile",
     *     summary="Lấy thông tin người dùng",
     *     operationId="index",
     *     tags={"auth"},
     *     description="Lấy thông tin người dùng",
     *     security={{"bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Thông tin người dùng đã được lấy thành công.",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nguyễn Văn A"),
     *             @OA\Property(property="email", type="string", example="example@example.com"),
     *             @OA\Property(property="phone", type="string", example="0123456789"),
     *             @OA\Property(property="avatar", type="string", example="http://example.com/avatar.jpg"),
     *             @OA\Property(property="vouchers", type="array", 
     *                 @OA\Items(type="object", 
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="code", type="string", example="VOUCHER123"),
     *                     @OA\Property(property="discount_value", type="number", format="float", example=10.5)
     *                 )
     *             ),
     *             @OA\Property(property="addresses", type="array", 
     *                 @OA\Items(type="object", 
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="street", type="string", example="123 Đường ABC"),
     *                     @OA\Property(property="city", type="string", example="Hà Nội"),
     *                     @OA\Property(property="country", type="string", example="Việt Nam")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy người dùng.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Không tìm thấy người dùng.")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            // Tìm người dùng theo ID
            $user = User::with(['vouchers', 'addresses'])->select('id', 'name', 'email', 'phone', 'role', 'avatar')->findOrFail(auth()->user()->id);

            // Trả về thông tin người dùng dưới dạng JSON
            return $this->sendResponse($user, 'Thông tin người dùng đã được lấy thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy người dùng.', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/update-profile",
     *     summary="Cập nhật thông tin người dùng",
     *     operationId="update",
     *     tags={"auth"},
     *     description="Cập nhật thông tin người dùng, bao gồm tên, số điện thoại, email và avatar.",
     *     security={{"bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="Nguyễn Văn A", description="Tên người dùng", minLength=1),
     *                 @OA\Property(property="phone", type="string", example="0123456789", description="Số điện thoại người dùng", minLength=1),
     *                 @OA\Property(property="email", type="string", example="example@example.com", description="Địa chỉ email người dùng", minLength=1),
     *                  @OA\Property(property="_method", type="string", example="put"),
     *                 @OA\Property(property="avatar", type="string", format="binary", description="Tải lên hình ảnh avatar (bắt buộc nếu có)", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thông tin tài khoản đã được cập nhật thành công.",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nguyễn Văn A"),
     *             @OA\Property(property="phone", type="string", example="0123456789"),
     *             @OA\Property(property="email", type="string", example="example@example.com"),
     *             @OA\Property(property="avatar", type="string", example="http://example.com/avatar.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Lỗi định dạng dữ liệu đầu vào.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Lỗi định dạng"),
     *             @OA\Property(property="messages", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra trong quá trình cập nhật.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Có lỗi xảy ra. Vui lòng thử lại sau.")
     *         )
     *     )
     * )
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:15',
                'email' => 'required|string|email|max:255',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Lỗi định dạng',
                    'messages' => $validator->errors()
                ], 422); // 422 Unprocessable Entity
            }
            $user = User::findOrFail(auth()->user()->id);
            $user->name = $request->name;
            $user->phone = $request->phone;
            $user->email = $request->email;

            // Upload avatar to Cloudinary
            if ($request->hasFile('avatar')) {
                $uploadedFileUrl = Cloudinary::upload($request->file('avatar')->getRealPath())->getSecurePath();
                $user->avatar = $uploadedFileUrl;
            }

            $user->save();

            return $this->sendResponse($user, 'Thay đổi thông tin tài khoản thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Có lỗi xảy ra. Vui lòng thử lại sau.', ['error' => $th->getMessage()], 500);
        }
    }
}

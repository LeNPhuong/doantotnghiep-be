<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/auth/address/all",
     *     summary="Lấy danh sách địa chỉ của người dùng",
     *     tags={"auth"},
     *     description="API này trả về danh sách địa chỉ của người dùng hiện tại. Nếu không có địa chỉ nào, sẽ trả về thông báo 'Danh sách địa chỉ trống'.",
     *     security={{"bearer":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh sách địa chỉ thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="address", type="string", example="123 Main St"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Lấy danh sách địa chỉ thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Danh sách địa chỉ trống",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Danh sách địa chỉ trống")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $addresses = Address::where('user_id', auth()->user()->id)->get();
        if (count($addresses) == 0) {
            return $this->sendError('Danh sách địa chỉ trống');
        }
        return $this->sendResponse($addresses, 'Lấy danh sách địa chỉ thành công');
    }

    /**
     * @OA\Post(
     *     path="/api/auth/address/create",
     *     summary="Tạo mới địa chỉ cho người dùng",
     *     description="API này cho phép người dùng tạo một địa chỉ mới. Nếu địa chỉ mới được đánh dấu là active, tất cả các địa chỉ khác của người dùng sẽ được cập nhật để không còn active.",
     *     tags={"auth"},
     *     security={{"bearer":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="address", type="string", description="Địa chỉ của người dùng", example="123 Main St", maxLength=225),
     *             @OA\Property(property="active", type="boolean", description="Trạng thái active của địa chỉ", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thay đổi địa chỉ thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="address", type="string", example="123 Main St"),
     *                 @OA\Property(property="active", type="boolean", example=true)
     *             ),
     *             @OA\Property(property="message", type="string", example="Thay đổi địa chỉ thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lỗi định dạng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lỗi định dạng."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="error", type="string", example="Chi tiết lỗi")
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'address' => 'required|string|max:225',
                'active' => 'required|boolean',
            ]);

            // Nếu địa chỉ mới là active, hủy active các địa chỉ cũ của user
            if ($request->active) {
                $request->user()->addresses()->update(['active' => false]);
            }

            // Tạo địa chỉ mới
            $address = $request->user()->addresses()->create([
                'address' => $request->address,
                'active' => $request->active ?? false,
            ]);

            return $this->sendResponse($address, 'Thay đổi địa chỉ thành công');
        } catch (\Throwable $th) {
            // Trả về lỗi JSON nếu request không hợp lệ
            return $this->sendError('Lỗi định dạng.', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/auth/address/{id}",
     *     summary="Cập nhật địa chỉ của người dùng",
     *     description="API này cho phép người dùng cập nhật một địa chỉ. Nếu địa chỉ được đánh dấu là active, các địa chỉ khác của người dùng sẽ bị hủy active.",
     *     tags={"auth"},
     *     security={{"bearer":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của địa chỉ cần cập nhật",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="address", type="string", description="Địa chỉ mới của người dùng", example="456 Another St", maxLength=225),
     *             @OA\Property(property="active", type="boolean", description="Trạng thái active của địa chỉ", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật địa chỉ thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="address", type="string", example="456 Another St"),
     *                 @OA\Property(property="active", type="boolean", example=true)
     *             ),
     *             @OA\Property(property="message", type="string", example="Cập nhật địa chỉ thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Không có quyền sửa địa chỉ này",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không có quyền sửa địa chỉ này.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lỗi định dạng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lỗi định dạng."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="error", type="string", example="Chi tiết lỗi")
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'address' => 'required|string|max:225',
                'active' => 'boolean',
            ]);

            // Tìm địa chỉ cần sửa
            $address = Address::findOrFail($id);

            // Kiểm tra xem địa chỉ có thuộc về user hiện tại không
            if ($address->user_id !== $request->user()->id) {
                return $this->sendError('Không có quyền sửa địa chỉ này.', [], 403);
            }

            // Nếu địa chỉ mới là active, hủy active các địa chỉ cũ của user
            if ($request->active) {
                $request->user()->addresses()->update(['active' => false]);
            }

            // Cập nhật địa chỉ
            $address->update([
                'address' => $request->address,
                'active' => $request->active ?? $address->active,
            ]);

            return $this->sendResponse($address, 'Cập nhật địa chỉ thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Lỗi định dạng.', ['error' => $th->getMessage()], 404);
        }
    }
}

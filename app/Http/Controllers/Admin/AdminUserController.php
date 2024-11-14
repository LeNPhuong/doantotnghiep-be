<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminUserController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/admin/users", 
     *     summary="Lấy danh sách người dùng với thông tin phân loại và số người dùng mới trong tuần",
     *     tags={"admin/user"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Lọc người dùng theo vai trò (tùy chọn)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"user", "admin"},
     *             default="user"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thành công thống kê và danh sách người dùng",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_users", type="integer", description="Tổng số người dùng"),
     *             @OA\Property(property="total_user_role", type="integer", description="Tổng số người dùng có vai trò 'user'"),
     *             @OA\Property(property="total_admin_role", type="integer", description="Tổng số người dùng có vai trò 'admin'"),
     *             @OA\Property(property="total_new_users_this_week", type="integer", description="Tổng số người dùng mới trong tuần qua"),
     *             @OA\Property(property="users", type="array", 
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", description="ID của người dùng"),
     *                     @OA\Property(property="name", type="string", description="Tên của người dùng"),
     *                     @OA\Property(property="email", type="string", description="Địa chỉ email của người dùng"),
     *                     @OA\Property(property="role", type="string", description="Vai trò của người dùng"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", description="Ngày tạo người dùng"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", description="Ngày cập nhật người dùng")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy người dùng hoặc có lỗi khi lấy dữ liệu",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Lỗi định dạng.")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $users = $this->filterUsersByRole($request);

            // Tính tổng số lượng người dùng và theo từng vai trò
            $totalUsers = (clone $users)->count();
            $totalUserRole = (clone $users)->where('role', 'user')->count();
            $totalAdminRole = (clone $users)->where('role', 'admin')->count();

            // Tính tổng số người dùng mới trong tuần
            $oneWeekAgo = Carbon::now()->subWeek();
            $totalNewUsersThisWeek = (clone $users)->where('created_at', '>=', $oneWeekAgo)->count();

            // Lấy danh sách người dùng
            $userList = $users->get();

            if ($userList->isEmpty()) {
                return $this->sendResponse([
                    'total_users' => $totalUsers,
                    'total_user_role' => $totalUserRole,
                    'total_admin_role' => $totalAdminRole,
                    'total_new_users_this_week' => $totalNewUsersThisWeek,
                ], 'Chưa có người dùng');
            }

            return $this->sendResponse([
                'total_users' => $totalUsers,
                'total_user_role' => $totalUserRole,
                'total_admin_role' => $totalAdminRole,
                'total_new_users_this_week' => $totalNewUsersThisWeek,
                'users' => $userList
            ], 'Lấy người dùng thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Lỗi định dạng.', ['error' => $th->getMessage()], 404);
        }
    }


    private function filterUsersByRole(Request $request)
    {
        $role = $request->query('role');

        if ($role) {
            return User::where('role', $role)->get();
        }

        return User::all();
    }

    /**
     * @OA\Get(
     *     path="/api/admin/user/{id}",
     *     summary="Lấy thông tin chi tiết của người dùng theo ID",
     *     tags={"admin/user"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của người dùng cần truy xuất",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Truy xuất người dùng thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", description="ID của người dùng"),
     *             @OA\Property(property="name", type="string", description="Tên của người dùng"),
     *             @OA\Property(property="email", type="string", description="Địa chỉ email của người dùng"),
     *             @OA\Property(property="role", type="string", description="Vai trò của người dùng"),
     *             @OA\Property(property="created_at", type="string", format="date-time", description="Ngày tạo người dùng"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", description="Ngày cập nhật người dùng")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Người dùng không tồn tại",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Người dùng không tồn tại")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $user = User::find($id);
            return $this->sendResponse($user, 'Truy xuất người dùng thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Người dùng không tồn tại', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/user/{id}/edit",
     *     summary="Truy xuất thông tin người dùng để chỉnh sửa theo ID",
     *     tags={"admin/user"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của người dùng cần chỉnh sửa",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Truy xuất thông tin người dùng thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", description="ID của người dùng"),
     *             @OA\Property(property="name", type="string", description="Tên của người dùng"),
     *             @OA\Property(property="email", type="string", description="Địa chỉ email của người dùng"),
     *             @OA\Property(property="role", type="string", description="Vai trò của người dùng"),
     *             @OA\Property(property="created_at", type="string", format="date-time", description="Ngày tạo người dùng"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", description="Ngày cập nhật người dùng")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Người dùng không tồn tại",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Người dùng không tồn tại")
     *         )
     *     )
     * )
     */
    public function edit($id)
    {
        try {
            $user = User::find($id);
            return $this->sendResponse($user, 'Truy xuất người dùng thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Người dùng không tồn tại', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/user/{id}/update",
     *     summary="Cập nhật thông tin người dùng theo ID",
     *     tags={"admin/user"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của người dùng cần cập nhật",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dữ liệu cần cập nhật của người dùng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="phone", type="string", maxLength=20, description="Số điện thoại của người dùng"),
     *             @OA\Property(property="name", type="string", maxLength=255, description="Tên của người dùng"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, description="Địa chỉ email của người dùng"),
     *             @OA\Property(property="role", type="string", enum={"user", "admin"}, description="Vai trò của người dùng (user hoặc admin)"),
     *             @OA\Property(property="avatar", type="string", maxLength=255, description="Đường dẫn đến avatar của người dùng"),
     *             @OA\Property(property="active", type="boolean", description="Trạng thái hoạt động của người dùng (true hoặc false)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật người dùng thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", description="ID của người dùng"),
     *             @OA\Property(property="name", type="string", description="Tên của người dùng"),
     *             @OA\Property(property="email", type="string", description="Địa chỉ email của người dùng"),
     *             @OA\Property(property="role", type="string", description="Vai trò của người dùng"),
     *             @OA\Property(property="phone", type="string", description="Số điện thoại của người dùng"),
     *             @OA\Property(property="avatar", type="string", description="Đường dẫn đến avatar của người dùng"),
     *             @OA\Property(property="active", type="boolean", description="Trạng thái hoạt động của người dùng"),
     *             @OA\Property(property="created_at", type="string", format="date-time", description="Ngày tạo người dùng"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", description="Ngày cập nhật người dùng")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy người dùng",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Không tìm thấy người dùng.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="object", additionalProperties={})
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra khi cập nhật người dùng",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Có lỗi xảy ra. Vui lòng thử lại sau.")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);

            $validatedData = $request->validate([
                'phone' => 'nullable|string|max:20',
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'role' => 'nullable|in:user,admin', // Chỉ chấp nhận 'user' hoặc 'admin'
                'avatar' => 'nullable|string|max:255',
                'active' => 'nullable|boolean',
            ]);

            // Loại bỏ các trường không có trong request để giữ nguyên giá trị cũ
            $dataToUpdate = array_filter($validatedData, fn($value) => !is_null($value));

            $user->update($dataToUpdate);

            return $this->sendResponse($user, 'Cập nhật người dùng thành công');
        } catch (\Exception $th) {
            return $this->sendError('Không tìm thấy người dùng.', ['error' => $th->getMessage()], 404);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->validator->errors()], 422);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra. Vui lòng thử lại sau.', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/user/search",
     *     summary="Tìm kiếm người dùng theo từ khóa",
     *     tags={"admin/user"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Từ khóa để tìm kiếm người dùng",
     *         @OA\Schema(
     *             type="string",
     *             example="John Doe"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách người dùng tìm thấy",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", description="ID của người dùng"),
     *                 @OA\Property(property="name", type="string", description="Tên người dùng"),
     *                 @OA\Property(property="email", type="string", description="Địa chỉ email người dùng"),
     *                 @OA\Property(property="role", type="string", description="Vai trò người dùng"),
     *                 @OA\Property(property="phone", type="string", description="Số điện thoại của người dùng"),
     *                 @OA\Property(property="avatar", type="string", description="Đường dẫn tới avatar của người dùng"),
     *                 @OA\Property(property="active", type="boolean", description="Trạng thái hoạt động của người dùng"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", description="Ngày tạo người dùng"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", description="Ngày cập nhật người dùng")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi khi tìm kiếm người dùng",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Đã xảy ra lỗi trong quá trình tìm kiếm Người dùng.")
     *         )
     *     )
     * )
     */
    public function search(Request $request)
    {
        try {
            $inputSearch = $request->input('query');

            $user = User::search($inputSearch)->get();

            return $this->sendResponse($user, 'Người dùng tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm Người dùng', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/user/{id}/delete",
     *     summary="Xóa mềm người dùng",
     *     tags={"admin/user"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của người dùng cần xóa",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Người dùng đã được xóa mềm thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Người dùng đã được xóa mềm thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy người dùng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Không tìm thấy Người dùng.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi trong quá trình xóa người dùng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Đã xảy ra lỗi trong quá trình xóa người dùng.")
     *         )
     *     )
     * )
     */
    public function softDelete($id)
    {
        try {
            $user = User::findOrFail($id);

            $user->delete();

            return $this->sendResponse(null, 'Người dùng đã được xóa mềm thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy Người dùng.', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/user/{id}/restore",
     *     summary="Khôi phục người dùng đã bị xóa mềm",
     *     tags={"admin/user"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của người dùng cần khôi phục",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Người dùng đã được khôi phục thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Người dùng đã được khôi phục thành công."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Nguyễn Văn A"),
     *                 @OA\Property(property="email", type="string", example="nguyen@viana.com"),
     *                 @OA\Property(property="phone", type="string", example="0123456789"),
     *                 @OA\Property(property="avatar", type="string", example="avatar.jpg"),
     *                 @OA\Property(property="role", type="string", example="user"),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy người dùng đã xóa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Không tìm thấy Người dùng đã xóa.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi trong quá trình khôi phục người dùng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Đã xảy ra lỗi trong quá trình khôi phục người dùng.")
     *         )
     *     )
     * )
     */
    public function restore($id)
    {
        try {
            $user = User::onlyTrashed()->findOrFail($id);
            $user->restore();

            return $this->sendResponse($user, 'Người dùng đã được khôi phục thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy Người dùng đã xóa.', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/user/create",
     *     summary="Tạo người dùng mới",
     *     tags={"admin/user"},
     *     security={{"bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Thông tin người dùng cần tạo",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"name", "phone", "email", "password", "role"},
     *                 @OA\Property(property="name", type="string", maxLength=255, example="Nguyễn Văn A"),
     *                 @OA\Property(property="phone", type="string", maxLength=20, example="0123456789"),
     *                 @OA\Property(property="email", type="string", format="email", maxLength=255, example="nguyen@viana.com"),
     *                 @OA\Property(property="password", type="string", minLength=6, example="password123!"),
     *                 @OA\Property(property="role", type="string", enum={"user", "admin"}, example="user")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tạo người dùng thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Tạo người dùng thành công."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Nguyễn Văn A"),
     *                 @OA\Property(property="phone", type="string", example="0123456789"),
     *                 @OA\Property(property="email", type="string", example="nguyen@viana.com"),
     *                 @OA\Property(property="role", type="string", example="user"),
     *                 @OA\Property(property="avatar", type="string", example="avatar.jpg"),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Lỗi định dạng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Lỗi định dạng")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra trong quá trình tạo người dùng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Có lỗi xảy ra trong quá trình tạo người dùng")
     *         )
     *     )
     * )
     */
    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => [
                    'required',
                    'string',
                    'min:6',
                    'regex:/[a-zA-Z]/',      // ít nhất một chữ cái
                    'regex:/[0-9]/',          // ít nhất một số
                    'regex:/[^a-zA-Z0-9]/',   // ít nhất một ký tự đặc biệt
                ],
                'role' => 'required|in:user,admin', // Chỉ chấp nhận 'user' hoặc 'admin'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Lỗi định dạng', $validator->errors());
            }

            $input = $request->all();
            $input['password'] = bcrypt($input['password']);
            $user = User::create($input);
            $success['user'] = $user;

            return $this->sendResponse($success, 'Tạo người dùng thành công.');
        } catch (\Exception $e) {
            return $this->sendError('Có lỗi xảy ra trong quá trình tạo người dùng', ['error' => $e->getMessage()], 500);
        }
    }
}

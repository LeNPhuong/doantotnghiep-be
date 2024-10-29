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
    public function index(Request $request)
    {
        try {
            $users = $this->filterUsersByRole($request);
            $totalUsers = $users->count();
            $totalUserRole = $users->where('role', 'user')->count();
            $totalAdminRole = $users->where('role', 'admin')->count();

            // Tính tổng số người dùng mới trong tuần
            $oneWeekAgo = Carbon::now()->subWeek();
            $totalNewUsersThisWeek = $users->where('created_at', '>=', $oneWeekAgo)->count();

            if ($users->isEmpty()) {
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
                'users' => $users
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

    public function show($id)
    {
        try {
            $user = User::find($id);
            return $this->sendResponse($user, 'Truy xuất người dùng thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Người dùng không tồn tại', ['error' => $th->getMessage()], 404);
        }
    }
    public function edit($id)
    {
        try {
            $user = User::find($id);
            return $this->sendResponse($user, 'Truy xuất người dùng thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Người dùng không tồn tại', ['error' => $th->getMessage()], 404);
        }
    }

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

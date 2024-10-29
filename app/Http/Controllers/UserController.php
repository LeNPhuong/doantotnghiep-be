<?php

namespace App\Http\Controllers;

use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Tìm người dùng theo ID
            $user = User::with(['vouchers', 'addresses'])->select('id', 'name', 'email', 'phone', 'email', 'avatar')->findOrFail(auth()->user()->id);

            // Trả về thông tin người dùng dưới dạng JSON
            return $this->sendResponse($user, 'Thông tin người dùng đã được lấy thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy người dùng.', ['error' => $th->getMessage()], 404);
        }
    }
    /**
     * Update the specified resource in storage.
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

<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends BaseController
{
    public function index()
    {
        $addresses = Address::where('user_id', auth()->user()->id)->get();
        if (count($addresses) == 0) {
            return $this->sendError('Danh sách địa chỉ trống');
        }
        return $this->sendResponse($addresses, 'Lấy danh sách địa chỉ thành công');
    }
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

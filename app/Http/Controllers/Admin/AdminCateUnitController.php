<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Category_unit;
use Illuminate\Http\Request;

class AdminCateUnitController extends BaseController
{
    // public function index()
    // {
    //     try {
    //         $category_unit = Category_unit::withTrashed()->get();
    //         if ($category_unit->isEmpty()) {
    //             return $this->sendResponse($category_unit, 'Chưa có danh sách');
    //         }
    //         return $this->sendResponse($category_unit, 'Lấy danh sách thành công');
    //     } catch (\Throwable $th) {
    //         return $this->sendError('Lỗi định dạng.', ['error' => $th->getMessage()], 404);
    //     }
    // }

    public function softDelete($id)
    {
        try {
            $category_unit = Category_unit::find($id);

            // Xóa mềm danh mục
            $category_unit->delete();
            return $this->sendResponse(null, 'Xóa danh mục thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy danh mục.', ['error' => $th->getMessage()], 404);
        }
    }
}

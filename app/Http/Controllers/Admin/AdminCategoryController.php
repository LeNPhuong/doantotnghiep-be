<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class AdminCategoryController extends BaseController
{
    public function index()
    {
        try {
            $category = Category::all();
            if($category->isEmpty()) {
                return $this->sendResponse($category, 'Chưa có danh mục sản phẩm');
            }
            return $this->sendResponse($category, 'Lấy danh mục thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Lỗi định dạng.', ['error' => $th->getMessage()], 404);
        }
    }

    public function show($id)
    {
        try {
            $category = Category::find($id);
            return $this->sendResponse($category, 'Lấy danh mục thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy danh mục', ['error' => $th->getMessage()], 404);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $category =  Category::find($id);

            $validatedData = $request->validate([
                'name' => 'nullable|string|max:191',
                'key' => 'nullable|string|max:191',
                'active' => 'tinyint',
            ]);

            // Loại bỏ các trường không có trong request để giữ nguyên giá trị cũ
            $dataToUpdate = array_filter($validatedData, fn($value) => !is_null($value));

            $category->update($dataToUpdate);

            return $this->sendResponse($category, 'Cập nhật danh mục thành công');
        } catch (\Exception $th) {
            return $this->sendError('Không tìm thấy danh mục.', ['error' => $th->getMessage()], 404);
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

            $category = Category::search($inputSearch)->get();

            return $this->sendResponse($category, 'Danh mục tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm danh mục', ['error' => $th->getMessage()], 500);
        }
    }

    public function softDelete($id)
    {
        try {
            $category = Category::findOrFail($id);

            $category->delete();

            return $this->sendResponse(null, 'Danh mục đã được xóa mềm thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy danh mục.', ['error' => $th->getMessage()], 404);
        }
    }

    public function restore($id)
    {
        try {
            $category = Category::onlyTrashed()->findOrFail($id);
            $category->restore();

            return $this->sendResponse($category, 'Danh mục đã được khôi phục thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy danh mục đã xóa.', ['error' => $th->getMessage()], 404);
        }
    }

    public function create(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:191',
                'key' => 'required|string|max:191',
                'active' => 'tinyint',
            ]);

            $category = Category::create($validatedData);

            return $this->sendResponse($category, 'Danh mục đã được thêm thành công.');
        } catch (\Exception $e) {
            return $this->sendError('Có lỗi xảy ra trong quá trình thêm danh mục', ['error' => $e->getMessage()], 500);
        }
    }
}

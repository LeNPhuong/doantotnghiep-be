<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class AdminCategoryController extends BaseController
{
    public function index()
    {
        try {
            $category = Category::all();
            if ($category->isEmpty()) {
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
            $category = Category::with('units')->findOrFail($id);
            return $this->sendResponse($category, 'Lấy danh mục thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy danh mục', ['error' => $th->getMessage()], 404);
        }
    }
    public function edit($id)
    {
        try {
            $category = Category::with('units')->findOrFail($id);
            return $this->sendResponse($category, 'Lấy danh mục thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy danh mục', ['error' => $th->getMessage()], 404);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            // Tìm category cùng với các đơn vị liên kết từ bảng category_unit
            $category = Category::with('units')->findOrFail($id);

            // Xác thực dữ liệu từ request
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:191',
                'key' => 'nullable|string|max:191',
                'active' => 'boolean',
                'units' => 'array',
                'units.*.unit_id' => 'exists:units,id', 
            ]);

            // Lọc ra những dữ liệu cần cập nhật của category
            $dataToUpdate = array_filter($validatedData, fn($value) => !is_null($value));

            // Cập nhật thông tin của category
            $category->update($dataToUpdate);

            // Cập nhật hoặc thêm mới unit_id trong bảng category_unit nếu có danh sách đơn vị mới
            if (isset($validatedData['units'])) {
                // Lấy danh sách unit_id hiện tại
                $currentUnitIds = $category->units->pluck('id')->toArray();

                // Tạo mảng để lưu các unit_id mới
                $newUnitIds = array_column($validatedData['units'], 'unit_id');

                // Xóa các unit_id không còn trong danh sách mới
                foreach (array_diff($currentUnitIds, $newUnitIds) as $unitId) {
                    $category->units()->detach($unitId);
                }

                // Thêm mới các unit_id chưa có trong danh sách hiện tại
                foreach (array_diff($newUnitIds, $currentUnitIds) as $unitId) {
                    $category->units()->attach($unitId);
                }
            }

            return $this->sendResponse($category->load('units'), 'Cập nhật danh mục thành công');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Không tìm thấy danh mục.', ['error' => $e->getMessage()], 404);
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

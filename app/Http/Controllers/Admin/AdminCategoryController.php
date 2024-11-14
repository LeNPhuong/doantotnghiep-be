<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class AdminCategoryController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/admin/categories",
     *     summary="Lấy danh sách danh mục",
     *     description="API này dùng để lấy toàn bộ danh mục sản phẩm từ hệ thống.",
     *     tags={"admin/category"},
     *     security={{"bearer":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh mục thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Thời trang nam"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T09:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T09:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Lấy danh mục thành công"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lỗi định dạng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Lỗi định dạng."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Message lỗi")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $category = Category::withTrashed()->get();
            if ($category->isEmpty()) {
                return $this->sendResponse($category, 'Chưa có danh mục sản phẩm');
            }
            return $this->sendResponse($category, 'Lấy danh mục thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Lỗi định dạng.', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/category/{id}",
     *     summary="Lấy thông tin danh mục theo ID",
     *     description="API này dùng để lấy thông tin chi tiết của một danh mục, bao gồm cả các đơn vị liên kết (units) theo ID.",
     *     tags={"admin/category"},
     *     security={{"bearer":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của danh mục cần lấy thông tin",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thông tin danh mục thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Thời trang nam"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T09:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T09:00:00Z"),
     *                 @OA\Property(
     *                     property="units",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Cái"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T09:00:00Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T09:00:00Z")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Lấy danh mục thành công"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy danh mục",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Không tìm thấy danh mục"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Message lỗi")
     *             )
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $category = Category::with('units')->findOrFail($id);
            return $this->sendResponse($category, 'Lấy danh mục thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy danh mục', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/category/{id}/edit",
     *     summary="Lấy thông tin danh mục để chỉnh sửa",
     *     tags={"admin/category"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID của danh mục cần chỉnh sửa"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh mục thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Thời trang nam"),
     *                 @OA\Property(
     *                     property="units",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Lít"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="message", type="string", example="Lấy danh mục thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy danh mục",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy danh mục"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Message lỗi")
     *             )
     *         )
     *     )
     * )
     */
    public function edit($id)
    {
        try {
            $category = Category::with('units')->findOrFail($id);
            return $this->sendResponse($category, 'Lấy danh mục thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy danh mục', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/category/{id}/update",
     *     summary="Cập nhật thông tin danh mục",
     *     tags={"admin/category"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID của danh mục cần cập nhật"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Thời trang nữ", description="Tên của danh mục"),
     *             @OA\Property(property="key", type="string", example="fashion-women", description="Key của danh mục"),
     *             @OA\Property(property="active", type="boolean", example=true, description="Trạng thái hoạt động của danh mục"),
     *             @OA\Property(
     *                 property="units",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="unit_id", type="integer", example=1, description="ID của đơn vị")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật danh mục thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Thời trang nữ"),
     *                 @OA\Property(property="key", type="string", example="fashion-women"),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="units",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Lít"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="message", type="string", example="Cập nhật danh mục thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy danh mục",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy danh mục."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Message lỗi")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="object", example={"name": {"Tên không được để trống"}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra. Vui lòng thử lại sau.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra. Vui lòng thử lại sau."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Message lỗi")
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            // Tìm category cùng với các đơn vị liên kết từ bảng category_unit
            $category = Category::with('units')->find($id);

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

    /**
     * @OA\Get(
     *     path="/api/admin/categories/search",
     *     summary="Tìm kiếm danh mục",
     *     description="Tìm kiếm danh mục theo tên hoặc các thuộc tính khác của danh mục.",
     *     tags={"admin/category"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Từ khóa tìm kiếm trong tên hoặc thuộc tính của danh mục.",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="example search"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách danh mục tìm thấy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Danh mục tìm thấy"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Danh mục A"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T10:00:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy danh mục",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy danh mục"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi tìm kiếm danh mục",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi trong quá trình tìm kiếm danh mục"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="error", type="string", example="Error details")
     *             )
     *         )
     *     )
     * )
     */
    public function search(Request $request)
    {
        try {
            $inputSearch = $request->input('query');

            $category = Category::search($inputSearch)->get();

            if ($category->isEmpty()) {
                return $this->sendResponse($category, 'Không tìm thấy danh mục');
            }

            return $this->sendResponse($category, 'Danh mục tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm danh mục', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/category/{id}/soft-delete",
     *     summary="Xóa mềm danh mục",
     *     description="Xóa mềm một danh mục dựa trên ID.",
     *     tags={"admin/category"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của danh mục cần xóa mềm",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh mục đã được xóa mềm thành công.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Danh mục đã được xóa mềm thành công."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="null"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy danh mục",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Không tìm thấy danh mục."
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Không tìm thấy danh mục."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Lỗi hệ thống. Vui lòng thử lại sau."
     *             )
     *         )
     *     )
     * )
     */
    public function softDelete($id)
    {
        try {
            $category = Category::find($id);

            $category->delete();

            return $this->sendResponse(null, 'Danh mục đã được xóa mềm thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy danh mục.', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/category/{id}/restore",
     *     summary="Khôi phục danh mục đã xóa mềm",
     *     description="Khôi phục một danh mục đã xóa mềm dựa trên ID.",
     *     tags={"admin/category"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của danh mục đã xóa mềm cần khôi phục",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh mục đã được khôi phục thành công",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Danh mục đã được khôi phục thành công."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="Danh mục A"
     *                 ),
     *                 @OA\Property(
     *                     property="key",
     *                     type="string",
     *                     example="category-a"
     *                 ),
     *                 @OA\Property(
     *                     property="active",
     *                     type="boolean",
     *                     example=true
     *                 ),
     *                 @OA\Property(
     *                     property="created_at",
     *                     type="string",
     *                     format="date-time",
     *                     example="2024-11-01T10:00:00Z"
     *                 ),
     *                 @OA\Property(
     *                     property="updated_at",
     *                     type="string",
     *                     format="date-time",
     *                     example="2024-11-01T10:00:00Z"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy danh mục đã xóa",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Không tìm thấy danh mục đã xóa."
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Không tìm thấy danh mục đã xóa."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Lỗi hệ thống. Vui lòng thử lại sau."
     *             )
     *         )
     *     )
     * )
     */
    public function restore($id)
    {
        try {
            $category = Category::onlyTrashed()->find($id);
            $category->restore();

            return $this->sendResponse($category, 'Danh mục đã được khôi phục thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy danh mục đã xóa.', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/category/create",
     *     summary="Thêm mới danh mục",
     *     description="Tạo mới một danh mục với tên, khóa và trạng thái hoạt động.",
     *     tags={"admin/category"},
     *     security={{"bearer": {}}},
     *      @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"name", "key", "active"},
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     maxLength=191,
     *                     example="Danh mục mới"
     *                 ),
     *                 @OA\Property(
     *                     property="key",
     *                     type="string",
     *                     maxLength=191,
     *                     example="danh-muc-moi"
     *                 ),
     *                 @OA\Property(
     *                     property="active",
     *                     type="integer",
     *                     example=1
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh mục đã được thêm thành công.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Danh mục đã được thêm thành công."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="Danh mục mới"
     *                 ),
     *                 @OA\Property(
     *                     property="key",
     *                     type="string",
     *                     example="danh-muc-moi"
     *                 ),
     *                 @OA\Property(
     *                     property="active",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="created_at",
     *                     type="string",
     *                     format="date-time",
     *                     example="2024-11-10T12:34:56"
     *                 ),
     *                 @OA\Property(
     *                     property="updated_at",
     *                     type="string",
     *                     format="date-time",
     *                     example="2024-11-10T12:34:56"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra trong quá trình thêm danh mục.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Có lỗi xảy ra trong quá trình thêm danh mục"
     *             )
     *         )
     *     )
     * )
     */
    public function create(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:191',
                'key' => 'required|string|max:191',
                'active' => 'required|boolean',
            ]);

            $category = Category::create($validatedData);

            return $this->sendResponse($category, 'Danh mục đã được thêm thành công.');
        } catch (\Exception $e) {
            return $this->sendError('Có lỗi xảy ra trong quá trình thêm danh mục', ['error' => $e->getMessage()], 500);
        }
    }
}

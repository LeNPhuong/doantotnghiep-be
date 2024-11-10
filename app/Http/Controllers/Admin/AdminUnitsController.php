<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Unit;
use Illuminate\Contracts\Support\ValidatedData;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminUnitsController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/admin/units",
     *     summary="Lấy danh sách đơn vị",
     *     description="Trả về danh sách tất cả các đơn vị trong hệ thống.",
     *     tags={"admin/unit"},
     *     security={{"bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh sách đơn vị thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy danh sách đơn vị thành công"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Đơn vị 1"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T12:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T12:00:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi lấy danh sách đơn vị",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra. Vui lòng thử lại sau."),
     *             @OA\Property(property="error", type="string", example="Thông tin lỗi hệ thống")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $units = Unit::all();
            return $this->sendResponse($units, 'Lấy danh sách đơn vị thành công');
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra. Vui lòng thử lại sau.', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/units/search",
     *     summary="Tìm kiếm đơn vị",
     *     description="Tìm kiếm đơn vị theo từ khóa nhập vào.",
     *     tags={"admin/unit"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Từ khóa tìm kiếm đơn vị",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="Đơn vị 1"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đơn vị tìm thấy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đơn vị tìm thấy"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Đơn vị 1"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T12:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T12:00:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi tìm kiếm đơn vị",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi trong quá trình tìm kiếm Đơn vị"),
     *             @OA\Property(property="error", type="string", example="Thông tin lỗi hệ thống")
     *         )
     *     )
     * )
     */
    public function search(Request $request)
    {
        try {
            $inputSearch = $request->input('query');

            $Units = Unit::search($inputSearch)->get();

            return $this->sendResponse($Units, 'Đơn vị tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm Đơn vị', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/units/edit/{id}",
     *     summary="Lấy thông tin đơn vị",
     *     description="Lấy thông tin chi tiết của đơn vị theo ID.",
     *     tags={"admin/unit"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của đơn vị cần lấy thông tin",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thông tin đơn vị tìm thấy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy thông tin đơn vị thành công"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Đơn vị 1"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T12:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T12:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Đơn vị không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đơn vị không tồn tại")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi lấy thông tin đơn vị",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra. Vui lòng thử lại sau."),
     *             @OA\Property(property="error", type="string", example="Thông tin lỗi hệ thống")
     *         )
     *     )
     * )
     */
    public function edit($id)
    {
        try {
            $unit = Unit::findOrFail($id);
            return $this->sendResponse($unit, 'Lấy thông tin đơn vị thành công');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Đơn vị không tồn tại', [], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/units/update/{id}",
     *     tags={"admin/unit"},
     *     summary="Cập nhật thông tin đơn vị",
     *     description="Cập nhật thông tin đơn vị dựa trên ID.",
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của đơn vị cần cập nhật",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dữ liệu cần cập nhật cho đơn vị",
     *         @OA\JsonContent(
     *             required={"name", "active"},
     *             @OA\Property(property="name", type="string", maxLength=191, description="Tên đơn vị"),
     *             @OA\Property(property="active", type="boolean", description="Trạng thái hoạt động")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật đơn vị thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", description="ID của đơn vị"),
     *                 @OA\Property(property="name", type="string", description="Tên đơn vị"),
     *                 @OA\Property(property="active", type="boolean", description="Trạng thái hoạt động"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", description="Thời gian tạo"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", description="Thời gian cập nhật")
     *             ),
     *             @OA\Property(property="message", type="string", example="Cập nhật đơn vị thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Đơn vị không tồn tại",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Đơn vị không tồn tại")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi khi cập nhật đơn vị",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra khi cập nhật đơn vị."),
     *             @OA\Property(property="error", type="string", example="Error message")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $unit = Unit::findOrFail($id);
            $validatedData = $request->validate([
                'name' => 'required|string|max:191',
                'active' => 'boolean',
            ]);
            $unit->update($validatedData);

            return $this->sendResponse($unit, 'Cập nhật đơn vị thành công');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Đơn vị không tồn tại', [], 404);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi cập nhật đơn vị.', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/units/delete/{id}",
     *     tags={"admin/unit"},
     *     summary="Xóa đơn vị",
     *     description="Xóa đơn vị dựa trên ID.",
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của đơn vị cần xóa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xóa đơn vị thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Xóa đơn vị thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Đơn vị không tồn tại",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Đơn vị không tồn tại")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi khi xóa đơn vị",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra khi xóa đơn vị."),
     *             @OA\Property(property="error", type="string", example="Error message")
     *         )
     *     )
     * )
     */
    public function delete($id)
    {
        try {
            $unit = Unit::findOrFail($id);
            $unit->delete();

            return $this->sendResponse(null, 'Xóa đơn vị thành công');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Đơn vị không tồn tại', [], 404);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi xóa đơn vị.', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/units/restore/{id}",
     *     tags={"admin/unit"},
     *     summary="Khôi phục đơn vị",
     *     description="Khôi phục đơn vị bị xóa mềm dựa trên ID.",
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của đơn vị cần khôi phục",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Khôi phục đơn vị thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", description="ID của đơn vị"),
     *                 @OA\Property(property="name", type="string", description="Tên đơn vị"),
     *                 @OA\Property(property="active", type="boolean", description="Trạng thái hoạt động"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", description="Thời gian tạo"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", description="Thời gian cập nhật"),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", description="Thời gian xóa mềm")
     *             ),
     *             @OA\Property(property="message", type="string", example="Khôi phục đơn vị thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Đơn vị không cần khôi phục vì chưa bị xóa",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Đơn vị không cần khôi phục vì chưa bị xóa.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Đơn vị không tồn tại",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Đơn vị không tồn tại")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi khi khôi phục đơn vị",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra khi khôi phục đơn vị."),
     *             @OA\Property(property="error", type="string", example="Error message")
     *         )
     *     )
     * )
     */
    public function restore($id)
    {
        try {
            $unit = Unit::withTrashed()->findOrFail($id);

            if ($unit->trashed()) {
                $unit->restore();
                return $this->sendResponse($unit, 'Khôi phục đơn vị thành công');
            }

            return $this->sendError('Đơn vị không cần khôi phục vì chưa bị xóa.', [], 400);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Đơn vị không tồn tại', [], 404);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi khôi phục đơn vị.', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/units/create", 
     *     summary="Create a new unit",
     *     tags={"admin/unit"},
     *     security={{"bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=191, description="The name of the unit"),
     *             @OA\Property(property="description", type="string", maxLength=500, description="A description of the unit")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unit created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", 
     *                 @OA\Property(property="id", type="integer", description="The ID of the unit"),
     *                 @OA\Property(property="name", type="string", description="The name of the unit"),
     *                 @OA\Property(property="description", type="string", description="A description of the unit"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", description="Creation date of the unit"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", description="Last update date of the unit")
     *             ),
     *             @OA\Property(property="message", type="string", example="Tạo đơn vị thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while creating the unit",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Có lỗi xảy ra khi tạo đơn vị.")
     *         )
     *     )
     * )
     */
    public function create(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:191',
                'description' => 'nullable|string|max:500'
            ]);

            $unit = Unit::create($validatedData);

            return $this->sendResponse($unit, 'Tạo đơn vị thành công');
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi tạo đơn vị.', ['error' => $th->getMessage()], 500);
        }
    }
}

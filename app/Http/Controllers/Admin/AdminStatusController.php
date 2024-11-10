<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Status;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class AdminStatusController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/admin/status",
     *     summary="Lấy danh sách trạng thái",
     *     description="Trả về danh sách tất cả các trạng thái có sẵn.",
     *     tags={"admin/status"},
     *     security={{"bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách trạng thái",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy danh sách trạng thái thành công"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Đang xử lý"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T10:00:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi lấy danh sách trạng thái",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra. Vui lòng thử lại sau."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="error", type="string", example="Error details")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $Status = Status::all();
            return $this->sendResponse($Status, 'Lấy danh sách trạng thái thành công');
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra. Vui lòng thử lại sau.', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/status/search",
     *     summary="Tìm kiếm trạng thái",
     *     description="Tìm kiếm trạng thái dựa trên từ khóa nhập vào.",
     *     tags={"admin/status"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Từ khóa để tìm kiếm trạng thái",
     *         @OA\Schema(type="string", example="Đang xử lý")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách trạng thái tìm thấy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Trạng thái tìm thấy"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Đang xử lý"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T10:00:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi tìm kiếm trạng thái",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi trong quá trình tìm kiếm trạng thái"),
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

            $Status = Status::search($inputSearch)->get();
            if($Status->isEmpty()){
                return $this->sendError('Không tìm thấy trạng thái', [], 404);
            }
            return $this->sendResponse($Status, 'trạng thái tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm trạng thái', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/status/edit/{id}",
     *     summary="Lấy thông tin trạng thái theo ID",
     *     description="Trả về thông tin của trạng thái theo ID.",
     *     tags={"admin/status"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của trạng thái cần chỉnh sửa",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thông tin trạng thái tìm thấy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy thông tin trạng thái thành công"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Đang xử lý"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T10:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Trạng thái không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Trạng thái không tồn tại"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi lấy thông tin trạng thái",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi trong quá trình lấy thông tin trạng thái"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="error", type="string", example="Error details")
     *             )
     *         )
     *     )
     * )
     */
    public function edit($id)
    {
        try {
            $unit = Status::findOrFail($id);
            return $this->sendResponse($unit, 'Lấy thông tin trạng thái thành công');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('trạng thái không tồn tại', [], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/status/update/{id}",
     *     summary="Cập nhật thông tin trạng thái",
     *     description="Cập nhật trạng thái theo ID.",
     *     tags={"admin/status"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của trạng thái cần cập nhật",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dữ liệu cần cập nhật trạng thái",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="text_status", type="string", example="Đang xử lý"),
     *             @OA\Property(property="active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật trạng thái thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cập nhật trạng thái thành công"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="text_status", type="string", example="Đang xử lý"),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T10:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Trạng thái không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Trạng thái không tồn tại"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi cập nhật trạng thái",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra khi cập nhật trạng thái."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="error", type="string", example="Error details")
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {

        try {
            $unit = Status::findOrFail($id);
            $validatedData = $request->validate([
                'text_status' => 'required|string|max:191',
                'active' => 'boolean',
            ]);
            $unit->update($validatedData);

            return $this->sendResponse($unit, 'Cập nhật trạng thái thành công');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('trạng thái không tồn tại', [], 404);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi cập nhật trạng thái.', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/status/delete/{id}",
     *     summary="Xóa trạng thái",
     *     description="Xóa trạng thái bằng ID.",
     *     tags={"admin/status"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của trạng thái cần xóa.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xóa trạng thái thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Xóa trạng thái thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Trạng thái không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="trạng thái không tồn tại")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi xảy ra khi xóa trạng thái",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra khi xóa trạng thái.")
     *         )
     *     )
     * )
     */
    public function delete($id)
    {
        try {
            $unit = Status::findOrFail($id);
            $unit->delete();

            return $this->sendResponse(null, 'Xóa trạng thái thành công');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('trạng thái không tồn tại', [], 404);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi xóa trạng thái.', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/status/restore/{id}",
     *     summary="Khôi phục trạng thái",
     *     description="Khôi phục trạng thái đã bị xóa mềm (soft delete).",
     *     tags={"admin/status"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của trạng thái cần khôi phục.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Khôi phục trạng thái thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Khôi phục trạng thái thành công"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="text_status", type="string", example="Đang xử lý"),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T12:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T12:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Trạng thái không cần khôi phục vì chưa bị xóa.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="trạng thái không cần khôi phục vì chưa bị xóa.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Trạng thái không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="trạng thái không tồn tại")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi xảy ra khi khôi phục trạng thái",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra khi khôi phục trạng thái.")
     *         )
     *     )
     * )
     */
    public function restore($id)
    {
        try {
            $unit = Status::withTrashed()->findOrFail($id);

            if ($unit->trashed()) {
                $unit->restore();
                return $this->sendResponse($unit, 'Khôi phục trạng thái thành công');
            }

            return $this->sendError('trạng thái không cần khôi phục vì chưa bị xóa.', [], 400);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('trạng thái không tồn tại', [], 404);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi khôi phục trạng thái.', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/status/create",
     *     summary="Tạo trạng thái mới",
     *     description="Tạo trạng thái mới với thông tin về tên trạng thái và trạng thái hoạt động.",
     *     tags={"admin/status"},
     *     security={{"bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Thông tin tạo trạng thái",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"text_status"},
     *             @OA\Property(property="text_status", type="string", maxLength=191, example="Đang xử lý"),
     *             @OA\Property(property="active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tạo trạng thái thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tạo trạng thái thành công"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="text_status", type="string", example="Đang xử lý"),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T12:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T12:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi tạo trạng thái",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra khi tạo trạng thái.")
     *         )
     *     )
     * )
     */
    public function create(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'text_status' => 'required|string|max:191',
                'active' => 'boolean'
            ]);

            $unit = Status::create($validatedData);

            return $this->sendResponse($unit, 'Tạo trạng thái thành công');
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi tạo trạng thái.', ['error' => $th->getMessage()], 500);
        }
    }
}

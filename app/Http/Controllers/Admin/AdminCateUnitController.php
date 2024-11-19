<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Category_unit;
use Illuminate\Http\Request;

class AdminCateUnitController extends BaseController
{

    /**
     * @OA\Delete(
     *     path="/api/admin/delete/cate-units/{id}",
     *     summary="Xóa mềm danh mục",
     *     description="Xóa mềm một danh mục unit theo id",
     *     tags={"admin/orders"},
     *     security={{"bearer":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của danh mục unit cần xóa",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xóa danh mục thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Xóa danh mục thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy danh mục",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy danh mục."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Không tìm thấy danh mục.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi khi xóa danh mục."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Lỗi server")
     *             )
     *         )
     *     )
     * )
     */
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

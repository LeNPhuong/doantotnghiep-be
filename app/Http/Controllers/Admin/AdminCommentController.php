<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Comment;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class AdminCommentController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/admin/comments/{id}",
     *     summary="Lấy thông tin sản phẩm và bình luận của sản phẩm",
     *     description="Lấy thông tin sản phẩm cùng với các bình luận, nếu có",
     *     security={{"bearer": {}}},
     *     tags={"admin/comment"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của sản phẩm cần lấy thông tin",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy sản phẩm và bình luận thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy sản phẩm và bình luận thành công"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Sản phẩm A"),
     *                 @OA\Property(property="price", type="number", format="float", example=100),
     *                 @OA\Property(
     *                     property="comments",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="content", type="string", example="Bình luận tuyệt vời!"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T14:00:00Z")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sản phẩm không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Sản phẩm không tồn tại."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra trong quá trình xử lý",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra. Vui lòng thử lại sau."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index($id)
    {
        try {
            // Tìm sản phẩm bao gồm cả các sản phẩm đã bị xóa mềm cùng với các bình luận
            $product = Product::withTrashed()->with('comments')->findOrFail($id);

            // Kiểm tra nếu sản phẩm không có bình luận
            if ($product->comments->isEmpty()) {
                return $this->sendResponse($product, 'Chưa có bình luận cho sản phẩm này');
            }

            // Nếu có bình luận, trả về dữ liệu sản phẩm và bình luận
            return $this->sendResponse($product, 'Lấy sản phẩm và bình luận thành công');
        } catch (ModelNotFoundException $e) {
            // Xử lý lỗi khi không tìm thấy sản phẩm
            return $this->sendError('Sản phẩm không tồn tại.', [], 404);
        } catch (\Exception $e) {
            // Xử lý lỗi chung
            return $this->sendError('Có lỗi xảy ra. Vui lòng thử lại sau.', ['error' => $e->getMessage()], 500);
        }
    }


    /**
     * @OA\Delete(
     *     path="/api/admin/comments/delete/{id}",
     *     summary="Xóa bình luận",
     *     description="Xóa bình luận theo ID (xóa mềm)",
     *     tags={"admin/comment"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của bình luận cần xóa",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bình luận đã được xóa mềm thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bình luận đã được xóa mềm thành công"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy bình luận",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy bình luận."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra trong quá trình xóa bình luận",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra. Vui lòng thử lại sau."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function delete($id)
    {
        try {
            // Tìm bình luận theo ID
            $comment = Comment::findOrFail($id);

            // Cập nhật thuộc tính active về 0 trước khi xóa
            $comment->active = 0;
            $comment->save();  // Lưu thay đổi

            // Xóa mềm bình luận
            $comment->delete();

            return $this->sendResponse(null, 'Bình luận đã được xóa mềm và trạng thái active đã được chuyển về 0 thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy bình luận.', ['error' => $th->getMessage()], 404);
        }
    }


    /**
     * @OA\Patch(
     *     path="/api/admin/comments/restore/{id}",
     *     summary="Khôi phục bình luận đã xóa",
     *     description="Khôi phục bình luận đã xóa (xóa mềm) theo ID",
     *     tags={"admin/comment"},
     *     security={{"bearer": {}}},
     *    @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của bình luận cần khôi phục",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bình luận đã được khôi phục thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bình luận đã được khôi phục thành công."),
     *             @OA\Property(property="data", type="object", 
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="content", type="string", example="Bình luận hay!"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy bình luận đã xóa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy bình luận đã xóa."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra trong quá trình khôi phục bình luận",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra. Vui lòng thử lại sau."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function restore($id)
    {
        try {
            // Tìm bình luận đã bị xóa mềm
            $comment = Comment::onlyTrashed()->findOrFail($id);

            // Khôi phục bình luận
            $comment->restore();

            // Cập nhật thuộc tính active về 1 sau khi khôi phục
            $comment->active = 1;
            $comment->save();  // Lưu thay đổi

            return $this->sendResponse($comment, 'Bình luận đã được khôi phục và chuyển trạng thái active về 1 thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy bình luận đã xóa.', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/comment/search",
     *     summary="Tìm kiếm bình luận",
     *     description="Tìm kiếm bình luận theo nội dung bình luận hoặc tên người dùng.",
     *     tags={"admin/comment"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Từ khóa tìm kiếm trong bình luận hoặc tên người dùng.",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="example search"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách bình luận tìm thấy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bình luận tìm thấy"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="comment", type="string", example="This is a comment"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T10:00:00"),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="name", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john@example.com")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy bình luận",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy bình luận"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống khi tìm kiếm bình luận",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi trong quá trình tìm kiếm bình luận"),
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

            $comment = comment::withTrashed()->with('user')->get();

            // Tìm kiếm trong 'code' của đơn hàng và 'name' của người dùng
            $filterComment = $comment->filter(function ($comment) use ($inputSearch) {
                return (strpos(strtolower($comment->comment), strtolower($inputSearch)) !== false) ||
                    (isset($comment->user) && strpos(strtolower($comment->user->name),strtolower($inputSearch)) !== false);
            });

            if ($filterComment->isEmpty()) {
                return $this->sendResponse($filterComment, 'Không tìm thấy bình luận');
            }

            return $this->sendResponse($filterComment, 'Bình luận tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm bình luận', ['error' => $th->getMessage()], 500);
        }
    }
}

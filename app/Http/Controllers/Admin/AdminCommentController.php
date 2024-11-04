<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Comment;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class AdminCommentController extends BaseController
{
    public function index($id)
    {
        try {
            // Tìm sản phẩm cùng với các bình luận
            $product = Product::with('comments')->findOrFail($id);

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

    public function delete($id)
    {
        try {
            $comments = Comment::find($id);

            $comments->delete();

            return $this->sendResponse(null, 'Bình luận đã được xóa mềm thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy bình luận.', ['error' => $th->getMessage()], 404);
        }
    }

    public function restore($id)
    {
        try {
            $comments = Comment::onlyTrashed()->findOrFail($id);
            $comments->restore();

            return $this->sendResponse($comments, 'Bình luận đã được khôi phục thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy bình luận đã xóa.', ['error' => $th->getMessage()], 404);
        }
    }

    public function search(Request $request)
    {
        try {
            $inputSearch = $request->input('query');

            $comment = Comment::search($inputSearch)->get();

            return $this->sendResponse($comment, 'Bình luận tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm bình luận', ['error' => $th->getMessage()], 500);
        }
    }
}

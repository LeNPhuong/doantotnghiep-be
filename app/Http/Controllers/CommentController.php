<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CommentController extends BaseController
{
    public function show($productId)
    {
        // Lấy danh sách bình luận có product_id tương ứng
        $comments = Comment::where('product_id', $productId)->get();

        return $this->sendResponse($comments, 'Lấy danh sách comment thành công');
    }
    public function store(Request $request, $productId)
    {
        // Kiểm tra nếu sản phẩm tồn tại
        $product = Product::find($productId);
        if (!$product) {
            return $this->sendError('Sản phẩm không tồn tại', [], 404);
        }
        // Tạo bộ validate cho dữ liệu bình luận
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000', // Tối đa 1000 ký tự cho bình luận
        ]);

        // Nếu validate thất bại, trả về JSON với lỗi và status code 422
        if ($validator->fails()) {
            return $this->sendError('Lỗi định dạng', $validator->errors());
        }

        // Tạo bình luận mới nếu validate thành công
        $comment = Comment::create([
            'product_id' => $productId,
            'user_id' => auth()->user()->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Trả về phản hồi thành công
        return $this->sendResponse($comment, 'Bình luận thành công.'); // 201 Created
    }


    public function toggleLike($commentId)
    {
        try {
            // Tìm comment theo ID, nếu không tìm thấy sẽ trả về lỗi 404
            $comment = Comment::findOrFail($commentId);

            // Lấy người dùng hiện tại
            $userId = auth()->user()->id;

            // Kiểm tra nếu người dùng đã like bình luận này
            $liked = DB::table('comment_likes')
                ->where('comment_id', $commentId)
                ->where('user_id', $userId)
                ->exists();
            // Xóa cache sau khi cập nhật
            Cache::forget('active_products');
            if ($liked) {
                // Nếu đã like, xóa bản ghi và giảm số like
                DB::table('comment_likes')->where('comment_id', $commentId)->where('user_id', $userId)->delete();
                $comment->decrement('likes');
                return $this->sendResponse($comment, 'Bỏ thích bình luận thành công.');
            } else {
                // Nếu chưa like, tạo bản ghi mới và tăng số like
                DB::table('comment_likes')->insert([
                    'comment_id' => $commentId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $comment->increment('likes');
                return $this->sendResponse($comment, 'Thích bình luận thành công.');
            }
        } catch (\Throwable $th) {
            // Trả về lỗi nếu có vấn đề xảy ra
            return $this->sendError('Lỗi định dạng.', ['error' => $th->getMessage()], 404);
        }
    }
}

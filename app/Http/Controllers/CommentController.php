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
    /**
     * @OA\Get(
     *     path="/api/comments/{productId}",
     *     summary="Lấy danh sách bình luận cho sản phẩm",
     *     description="API này trả về danh sách bình luận của một sản phẩm dựa trên ID sản phẩm.",
     *     tags={"comment"},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         description="ID của sản phẩm",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh sách comment thành công",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="content", type="string", example="Sản phẩm rất tốt!"),
     *                 @OA\Property(property="rating", type="integer", example=5),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-01T10:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy sản phẩm hoặc bình luận",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Không tìm thấy sản phẩm hoặc bình luận!")
     *         )
     *     )
     * )
     */
    public function show($productId)
    {
        // Lấy danh sách bình luận có product_id tương ứng
        $comments = Comment::where('product_id', $productId)->get();

        return $this->sendResponse($comments, 'Lấy danh sách comment thành công');
    }

    /**
     * @OA\Post(
     *     path="/api/products/{productId}/comment",
     *     summary="Thêm bình luận cho sản phẩm",
     *     description="API này cho phép người dùng thêm một bình luận và đánh giá cho sản phẩm dựa trên ID sản phẩm.",
     *     tags={"comment"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         description="ID của sản phẩm",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"rating", "comment"},
     *             @OA\Property(property="rating", type="integer", format="int32", example=5, description="Đánh giá của người dùng, từ 1 đến 5"),
     *             @OA\Property(property="comment", type="string", example="Sản phẩm rất tốt!", description="Nội dung bình luận, tối đa 1000 ký tự")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bình luận thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=10),
     *             @OA\Property(property="rating", type="integer", example=5),
     *             @OA\Property(property="comment", type="string", example="Sản phẩm rất tốt!"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-01T10:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sản phẩm không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Sản phẩm không tồn tại")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Lỗi định dạng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="rating",
     *                     type="array",
     *                     @OA\Items(type="string", example="Trường rating là bắt buộc.")
     *                 ),
     *                 @OA\Property(
     *                     property="comment",
     *                     type="array",
     *                     @OA\Items(type="string", example="Trường comment là bắt buộc.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/comments/{commentId}/toggleLike",
     *     summary="Thích hoặc bỏ thích bình luận",
     *     description="API này cho phép người dùng thích hoặc bỏ thích một bình luận, đồng thời xóa cache liên quan.",
     *     tags={"comment"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="commentId",
     *         in="path",
     *         description="ID của bình luận",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thành công thích hoặc bỏ thích bình luận",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="comment_id", type="integer", example=1),
     *                 @OA\Property(property="likes", type="integer", example=10)
     *             ),
     *             @OA\Property(property="message", type="string", example="Thích bình luận thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy bình luận",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bình luận không tồn tại"),
     *             @OA\Property(property="error", type="string", example="Lỗi định dạng.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi máy chủ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lỗi định dạng.")
     *         )
     *     )
     * )
     */
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
            Cache::forget("product_detail_{$comment->product->id}");
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

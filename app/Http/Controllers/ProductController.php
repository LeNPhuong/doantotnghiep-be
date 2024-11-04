<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ProductController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Lấy danh sách tất cả sản phẩm",
     *     description="API này cho phép lấy danh sách tất cả sản phẩm đang hoạt động, kèm theo thông tin danh mục và đơn vị.",
     *     tags={"product"},
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh sách sản phẩm thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="cate_id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Sản phẩm A"),
     *                     @OA\Property(property="price", type="number", format="float", example=100000),
     *                     @OA\Property(property="sale", type="number", format="float", example=90000),
     *                     @OA\Property(property="img", type="string", example="image_url.jpg"),
     *                     @OA\Property(property="quantity", type="integer", example=50),
     *                     @OA\Property(property="description", type="string", example="Mô tả sản phẩm"),
     *                     @OA\Property(property="made", type="string", example="Việt Nam"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(
     *                         property="category",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Danh mục B"),
     *                         @OA\Property(property="active", type="integer", example=1),
     *                         @OA\Property(
     *                             property="units",
     *                             type="array",
     *                             @OA\Items(
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name", type="string", example="Đơn vị C"),
     *                                 @OA\Property(property="active", type="integer", example=1)
     *                             )
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Lấy sản phẩm thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy sản phẩm",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lỗi không tìm thấy sản phẩm")
     *         )
     *     )
     * )
     */
    public function index()
    {
        // Lấy tất cả sản phẩm với thông tin danh mục
        // $products = Product::with('category')->where('active', 1)->get();

        $products = Cache::remember('active_products', 60, function () {
            return Product::with([
                'category' => function ($query) {
                    $query->where('active', 1); // Lấy danh mục có active = 1
                }, 'category.units' => function ($query) {
                    $query->where('active', 1); // Lấy đơn vị có active = 1
                }
            ])
                ->select('id', 'cate_id', 'name', 'price', 'sale', 'img', 'quantity', 'description', 'made', 'active')
                ->get();
        });


        return $this->sendResponse($products, 'Lấy sản phẩm thành công');
    }

    /**
     * @OA\Get(
     *     path="/api/products/{id}",
     *     summary="Lấy chi tiết sản phẩm",
     *     description="API này lấy thông tin chi tiết của một sản phẩm, bao gồm danh mục, đơn vị và bình luận của sản phẩm.",
     *     tags={"product"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của sản phẩm cần lấy chi tiết",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy chi tiết sản phẩm thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="cate_id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Sản phẩm A"),
     *                 @OA\Property(property="price", type="number", format="float", example=100000),
     *                 @OA\Property(property="sale", type="number", format="float", example=90000),
     *                 @OA\Property(property="img", type="string", example="image_url.jpg"),
     *                 @OA\Property(property="quantity", type="integer", example=50),
     *                 @OA\Property(property="description", type="string", example="Mô tả sản phẩm"),
     *                 @OA\Property(property="made", type="string", example="Việt Nam"),
     *                 @OA\Property(property="active", type="integer", example=1),
     *                 @OA\Property(
     *                     property="category",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Danh mục B"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(
     *                         property="units",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Đơn vị C"),
     *                             @OA\Property(property="active", type="integer", example=1)
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="comments",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="product_id", type="integer", example=1),
     *                         @OA\Property(property="user_id", type="integer", example=3),
     *                         @OA\Property(property="rating", type="integer", example=4),
     *                         @OA\Property(property="comment", type="string", example="Sản phẩm rất tốt"),
     *                         @OA\Property(property="likes", type="integer", example=10),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
     *                         @OA\Property(
     *                             property="user",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=3),
     *                             @OA\Property(property="name", type="string", example="Nguyễn Văn A"),
     *                             @OA\Property(property="email", type="string", example="nguyenvana@example.com")
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Lấy sản phẩm chi tiết thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy sản phẩm",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Sản phẩm không tồn tại"),
     *             @OA\Property(property="data", type="object", example={"error": "Sản phẩm không tồn tại"})
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        // Sử dụng cache để lưu trữ chi tiết sản phẩm
        $productDetail = Cache::remember("product_detail_{$id}", 60, function () use ($id) {
            return Product::with([
                'category' => function ($query) {
                    $query->where('active', 1); // Lấy danh mục có active = 1
                }, 'category.units' => function ($query) {
                    $query->where('active', 1); // Lấy đơn vị có active = 1
                }, 'comments' => function ($query) {
                    $query->select('id', 'product_id', 'user_id', 'rating', 'comment', 'likes', 'created_at') // Chọn các trường từ bảng comments
                        ->where('product_id', '!=', null) // Chỉ lấy các bình luận của sản phẩm
                        ->with(['user:id,name,email']); // Eager load thông tin user (chỉ lấy id, name, email)
                }
            ])
                ->select('id', 'cate_id', 'name', 'price', 'sale', 'img', 'quantity', 'description', 'made', 'active')
                ->find($id); // Tìm sản phẩm theo ID
        });

        // Kiểm tra nếu sản phẩm tồn tại
        if (!$productDetail) {
            return $this->sendError('Sản phẩm không tồn tại', ['error' => 'Sản phẩm không tồn tại'], 404);
        }

        return $this->sendResponse($productDetail, 'Lấy sản phẩm chi tiết thành công');
    }

    /**
     * @OA\Post(
     *     path="/api/products/search",
     *     summary="Tìm kiếm sản phẩm",
     *     description="API này cho phép người dùng tìm kiếm sản phẩm dựa trên từ khóa.",
     *     tags={"product"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"query"},
     *             @OA\Property(property="query", type="string", example="sản phẩm A", description="Từ khóa tìm kiếm")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tìm kiếm sản phẩm thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="cate_id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Sản phẩm A"),
     *                     @OA\Property(property="price", type="number", format="float", example=100000),
     *                     @OA\Property(property="sale", type="number", format="float", example=90000),
     *                     @OA\Property(property="img", type="string", example="image_url.jpg"),
     *                     @OA\Property(property="quantity", type="integer", example=50),
     *                     @OA\Property(property="description", type="string", example="Mô tả sản phẩm"),
     *                     @OA\Property(property="made", type="string", example="Việt Nam"),
     *                     @OA\Property(property="active", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Tìm kiếm sản phẩm thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Lỗi định dạng đầu vào",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lỗi định dạng"),
     *             @OA\Property(property="data", type="object", 
     *                 @OA\Property(property="query", type="array", 
     *                     @OA\Items(type="string", example="Trường này là bắt buộc.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function search(Request $request)
    {
        // Kiểm tra yêu cầu đầu vào, đảm bảo 'query' không được để trống
        $validator = Validator::make($request->all(), [
            'query' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Lỗi định dạng', $validator->errors());
        }

        $searchTerm = $request->input('query');

        // Tìm kiếm sản phẩm
        $products = Product::search($searchTerm)->get();

        // Kiểm tra nếu không có sản phẩm nào được tìm thấy
        if ($products->isEmpty()) {
            return $this->sendError('Không tìm thấy sản phẩm nào với từ khóa: ' . $searchTerm);
        }

        return $this->sendResponse($products, 'Tìm kiếm sản phẩm thành công');
    }
}

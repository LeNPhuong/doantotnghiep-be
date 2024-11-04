<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoriesController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Lấy danh sách danh mục",
     *     description="API này trả về toàn bộ danh sách các danh mục có trong hệ thống.",
     *     tags={"category"},
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh mục thành công",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Danh mục A"),
     *                 @OA\Property(property="description", type="string", example="Mô tả của danh mục A"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lỗi định dạng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Lỗi định dạng.")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $category = Category::all();
            return $this->sendResponse($category, 'Lấy danh mục thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Lỗi định dạng.', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/categories/{categoryId}/products",
     *     summary="Lấy sản phẩm theo danh mục",
     *     description="API này lấy danh sách sản phẩm dựa trên ID của danh mục và chỉ trả về các sản phẩm và đơn vị có trạng thái active.",
     *     tags={"category"},
     *     @OA\Parameter(
     *         name="categoryId",
     *         in="path",
     *         description="ID của danh mục",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy sản phẩm theo danh mục thành công",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="cate_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Sản phẩm A"),
     *                 @OA\Property(property="price", type="number", format="float", example=100000),
     *                 @OA\Property(property="sale", type="number", format="float", example=90000),
     *                 @OA\Property(property="img", type="string", example="url/image.jpg"),
     *                 @OA\Property(property="quantity", type="integer", example=50),
     *                 @OA\Property(property="description", type="string", example="Mô tả sản phẩm A"),
     *                 @OA\Property(property="made", type="string", example="Vietnam"),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="category",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Danh mục A"),
     *                     @OA\Property(
     *                         property="units",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Đơn vị A")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Danh mục không tồn tại để tìm thấy sản phẩm",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Danh mục không tồn tại để tìm thấy sản phẩm!")
     *         )
     *     )
     * )
     */
    public function getProductsByCategory($categoryId)
    {
        $category = Category::find($categoryId);
        if (!$category) {
            return $this->sendError('Danh mục không tồn tại để tìm thấy sản phẩm!');
        }

        $products = Product::with([
            'category' => function ($query) {
                $query->where('active', 1); // Lấy danh mục có active = 1
            },
            'category.units' => function ($query) {
                $query->where('active', 1); // Lấy đơn vị có active = 1
            }
        ])
            ->where('cate_id', $categoryId) // Lọc theo category ID mong muốn
            ->select('id', 'cate_id', 'name', 'price', 'sale', 'img', 'quantity', 'description', 'made', 'active')
            ->get();
        if (!$products) {
            return $this->sendError('Danh mục không tồn tại để tìm thấy sản phẩm!');
        }

        return $this->sendResponse($products, 'Lấy sản phẩm theo danh mục thành công');
    }
}

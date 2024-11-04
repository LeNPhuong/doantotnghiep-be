<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoriesController extends BaseController
{
    public function index()
    {
        try {
            $category = Category::all();
            return $this->sendResponse($category, 'Lấy danh mục thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Lỗi định dạng.', ['error' => $th->getMessage()], 404);
        }
    }

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

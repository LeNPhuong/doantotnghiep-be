<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use PhpParser\Node\Stmt\TryCatch;

class AdminProductController extends BaseController
{
    public function index()
    {
        try {
            $products = Cache::remember('active_products', 60, function () {
                return Product::orderBy('created_at', 'desc')->get();
            });
            return $this->sendResponse($products, 'Lấy sản phẩm thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Lỗi trong quá trình lấy sản phẩm', ['error' => $th->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            // Tìm sản phẩm theo ID
            $product = Cache::remember("product_detail_{$id}", 60, function () use ($id) {
                return Product::with([
                    'category' => function ($query) {
                        $query->where('active', 1); // Lấy danh mục có active = 1
                    },
                    'category.units' => function ($query) {
                        $query->where('active', 1); // Lấy đơn vị có active = 1
                    }
                ])->find($id);
            });

            return $this->sendResponse($product, 'lấy sản phẩm thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Sản phẩm không tồn tại', ['error' => $th->getMessage()], 404);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            // Tìm sản phẩm theo ID
            $product = Cache::remember("product_detail_{$id}", 60, function () use ($id) {
                return Product::with([
                    'category' => function ($query) {
                        $query->where('active', 1); // Lấy danh mục có active = 1
                    },
                    'category.units' => function ($query) {
                        $query->where('active', 1); // Lấy đơn vị có active = 1 
                    }
                ])->find($id);
            });

            // Xác thực dữ liệu
            $validatedData = $request->validate([
                'cate_id' => 'nullable|exists:categories,id',
                'name' => 'nullable|string|max:255',
                'price' => 'nullable|numeric|min:0',
                'sale' => 'nullable|integer|min:0|max:100',
                'img' => 'nullable|string|max:255',
                'quantity' => 'nullable|integer|min:0',
                'description' => 'nullable|string',
                'made' => 'nullable|string|max:255',
                'active' => 'boolean',
            ]);

            // Loại bỏ các trường không có trong request để giữ nguyên giá trị cũ
            $dataToUpdate = array_filter($validatedData, fn($value) => !is_null($value));

            // Cập nhật sản phẩm
            $product->update($dataToUpdate);

            // Xóa cache sản phẩm (nếu cần)
            Cache::forget("product_detail_{$id}");
            Cache::forget('active_products');

            return $this->sendResponse($product, 'Cập nhật sản phẩm thành công');
        } catch (\Exception $th) {
            return $this->sendError('Không tìm thấy sản phẩm.', ['error' => $th->getMessage()], 404);
        } catch (ValidationException $e) {
            // Xử lý lỗi xác thực
            return response()->json(['error' => $e->validator->errors()], 422);
        } catch (\Exception $th) {
            // Xử lý các lỗi khác
            return $this->sendError('Có lỗi xảy ra. Vui lòng thử lại sau.', ['error' => $th->getMessage()], 500);
        }
    }

    public function search(Request $request) {
        try {
            $inputSearch = $request->input('query');

            $products = Product::search($inputSearch)->get();
    
            return $this->sendResponse($products, 'Sản phẩm tìm thấy');
        } catch (\Throwable $th) {            
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm sản phẩm',['error' => $th->getMessage()], 500);
        }
    }
    
}

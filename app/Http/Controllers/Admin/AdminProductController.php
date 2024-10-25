<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AdminProductController extends BaseController
{
    public function update(Request $request, $id)
    {
        try {
            // Tìm sản phẩm theo ID
            $product = Product::find($id);

            if (!$product) {
                return response()->json(['error' => 'Sản phẩm không tồn tại'], 404);
            }

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

            return response()->json(['message' => 'Cập nhật sản phẩm thành công', 'product' => $product], 200);

        } catch (ValidationException $e) {
            // Xử lý lỗi xác thực
            return response()->json(['error' => $e->validator->errors()], 422);
        } catch (\Exception $e) {
            // Xử lý các lỗi khác
            return response()->json(['error' => 'Đã xảy ra lỗi, vui lòng thử lại sau.'], 500);
        }
    }
}

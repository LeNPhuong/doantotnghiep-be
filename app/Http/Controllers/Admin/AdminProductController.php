<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Product;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AdminProductController extends BaseController
{
    public function index()
    {
        try {
            $products = Cache::remember('active_products', 60, function () {
                return Product::orderBy('created_at', 'desc')->get();
            });
            if ($products->isEmpty()) {
                return $this->sendResponse($products, 'Chưa có sản phẩm');
            }
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
                    'category.activeUnits' // Sử dụng quan hệ `activeUnits` đã khai báo với điều kiện `active = 1`
                ])->find($id);
            });

            return $this->sendResponse($product, 'Lấy sản phẩm thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Sản phẩm không tồn tại', ['error' => $th->getMessage()], 404);
        }
    }

    public function edit($id)
    {
        try {
            // Tìm sản phẩm theo ID
            $product = Cache::remember("product_detail_{$id}", 60, function () use ($id) {
                return Product::with([
                    'category' => function ($query) {
                        $query->where('active', 1); // Lấy danh mục có active = 1
                    },
                    'category.activeUnits' // Sử dụng quan hệ `activeUnits` đã khai báo với điều kiện `active = 1`
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
            $product = Cache::remember("product_detail_{$id}", 60, function () use ($id) {
                return Product::with([
                    'category' => function ($query) {
                        $query->where('active', 1); // Lấy danh mục có active = 1
                    },
                    'category.activeUnits' // Sử dụng quan hệ `activeUnits` đã khai báo với điều kiện `active = 1`
                ])->find($id);
            });

            $validatedData = $request->validate([
                'cate_id' => 'nullable|exists:categories,id',
                'name' => 'nullable|string|max:255',
                'price' => 'nullable|numeric|min:0',
                'sale' => 'nullable|integer|min:0|max:100',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'quantity' => 'nullable|integer|min:0',
                'description' => 'nullable|string',
                'made' => 'nullable|string|max:255',
                'active' => 'boolean',
            ]);

            // Loại bỏ các trường không có trong request để giữ nguyên giá trị cũ
            $dataToUpdate = array_filter($validatedData, fn ($value) => !is_null($value));

            // Xóa ảnh cũ và upload ảnh mới nếu có
            if ($request->hasFile('img')) {
                // Xóa ảnh cũ trên Cloudinary
                if ($product->img_public_id) {
                    Cloudinary::destroy($product->img_public_id);
                }

                // Upload ảnh mới lên Cloudinary
                $uploadedFile = Cloudinary::upload($request->file('img')->getRealPath());
                $dataToUpdate['img'] = $uploadedFile->getSecurePath(); // Cập nhật URL ảnh mới
                $dataToUpdate['img_public_id'] = $uploadedFile->getPublicId(); // Cập nhật public_id ảnh mới
            }
            $product->update($dataToUpdate);

            // Xóa cache sản phẩm (nếu cần)
            Cache::forget("product_detail_{$id}");
            Cache::forget('active_products');

            return $this->sendResponse($product, 'Cập nhật sản phẩm thành công');
        } catch (\Exception $th) {
            return $this->sendError('Không tìm thấy sản phẩm.', ['error' => $th->getMessage()], 404);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->validator->errors()], 422);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra. Vui lòng thử lại sau.', ['error' => $th->getMessage()], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $inputSearch = $request->input('query');

            $products = Product::search($inputSearch)->get();

            return $this->sendResponse($products, 'Sản phẩm tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm sản phẩm', ['error' => $th->getMessage()], 500);
        }
    }

    public function softDelete($id)
    {
        try {
            $product = Product::findOrFail($id);

            $product->delete();

            return $this->sendResponse(null, 'Sản phẩm đã được xóa mềm thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy sản phẩm.', ['error' => $th->getMessage()], 404);
        }
    }

    public function restore($id)
    {
        try {
            $product = Product::onlyTrashed()->findOrFail($id);
            $product->restore();

            return $this->sendResponse($product, 'Sản phẩm đã được khôi phục thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy sản phẩm đã xóa.', ['error' => $th->getMessage()], 404);
        }
    }

    public function create(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'cate_id' => 'required|exists:categories,id',
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'sale' => 'nullable|integer|min:0|max:100',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'quantity' => 'required|integer|min:0',
                'description' => 'nullable|string',
                'made' => 'nullable|string|max:255',
                'active' => 'boolean',
            ]);

            // Upload ảnh lên Cloudinary nếu có
            if ($request->hasFile('img')) {
                $uploadedFile = Cloudinary::upload($request->file('img')->getRealPath());
                $validatedData['img'] = $uploadedFile->getSecurePath(); // Lưu URL ảnh
                $validatedData['img_public_id'] = $uploadedFile->getPublicId(); // Lưu public_id ảnh
            }

            $product = Product::create($validatedData);

            Cache::forget('active_products');

            Cache::forget("product_detail_{$product->id}");

            return $this->sendResponse($product, 'Sản phẩm đã được thêm thành công.');
        } catch (\Exception $e) {
            return $this->sendError('Có lỗi xảy ra trong quá trình thêm sản phẩm', ['error' => $e->getMessage()], 500);
        }
    }
}

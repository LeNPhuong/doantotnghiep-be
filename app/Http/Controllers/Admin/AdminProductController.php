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
    /**
     * @OA\Get(
     *     path="/api/admin/products",
     *     summary="Lấy danh sách sản phẩm",
     *     description="Lấy danh sách các sản phẩm",
     *     tags={"admin/products"},
     *     security={{"bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách sản phẩm",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Sản phẩm A"),
     *                     @OA\Property(property="price", type="number", format="float", example=100000.0),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-15T12:45:00Z")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Lấy sản phẩm thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi trong quá trình lấy sản phẩm",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lỗi trong quá trình lấy sản phẩm"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="error", type="string", example="Chi tiết lỗi...")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $products = Cache::remember('active_products', 60, function () {
                return Product::withTrashed()->orderBy('created_at', 'desc')->get();
            });

            if ($products->isEmpty()) {
                return $this->sendResponse($products, 'Chưa có sản phẩm');
            }

            return $this->sendResponse($products, 'Lấy sản phẩm thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Lỗi trong quá trình lấy sản phẩm', ['error' => $th->getMessage()], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/admin/product/{id}",
     *     summary="Lấy thông tin chi tiết sản phẩm",
     *     description="Lấy chi tiết sản phẩm theo ID, bao gồm danh mục và các đơn vị hoạt động",
     *     tags={"admin/products"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID của sản phẩm"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sản phẩm đã được lấy thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", description="ID sản phẩm"),
     *                 @OA\Property(property="name", type="string", description="Tên sản phẩm"),
     *                 @OA\Property(property="price", type="number", format="float", description="Giá sản phẩm"),
     *                 @OA\Property(property="description", type="string", description="Mô tả sản phẩm"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", description="Ngày tạo sản phẩm"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", description="Ngày cập nhật sản phẩm"),
     *                 @OA\Property(property="category", type="object",
     *                     @OA\Property(property="id", type="integer", description="ID danh mục"),
     *                     @OA\Property(property="name", type="string", description="Tên danh mục"),
     *                     @OA\Property(property="active", type="boolean", description="Trạng thái danh mục")
     *                 ),
     *                 @OA\Property(property="units", type="array", 
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", description="ID đơn vị"),
     *                         @OA\Property(property="name", type="string", description="Tên đơn vị"),
     *                         @OA\Property(property="active", type="boolean", description="Trạng thái đơn vị")
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
     *             @OA\Property(property="error", type="string", description="Thông báo lỗi"),
     *             @OA\Property(property="message", type="string", description="Mô tả chi tiết lỗi"),
     *             @OA\Property(property="data", type="object", description="Dữ liệu trả về khi lỗi xảy ra")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            // Tìm sản phẩm theo ID, bao gồm sản phẩm đã xóa mềm
            $product = Cache::remember("product_detail_{$id}", 60, function () use ($id) {
                return Product::withTrashed() // Include soft-deleted products
                    ->with([
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


    /**
     * @OA\Get(
     *     path="/api/admin/products/{id}/update",
     *     summary="Lấy thông tin sản phẩm",
     *     description="Lấy chi tiết sản phẩm theo ID, bao gồm danh mục và các đơn vị hoạt động",
     *     tags={"admin/products"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID của sản phẩm"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy sản phẩm thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", description="ID sản phẩm"),
     *                 @OA\Property(property="name", type="string", description="Tên sản phẩm"),
     *                 @OA\Property(property="price", type="number", format="float", description="Giá sản phẩm"),
     *                 @OA\Property(property="description", type="string", description="Mô tả sản phẩm"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", description="Ngày tạo sản phẩm"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", description="Ngày cập nhật sản phẩm"),
     *                 @OA\Property(property="category", type="object",
     *                     @OA\Property(property="id", type="integer", description="ID danh mục"),
     *                     @OA\Property(property="name", type="string", description="Tên danh mục"),
     *                     @OA\Property(property="active", type="boolean", description="Trạng thái danh mục")
     *                 ),
     *                 @OA\Property(property="units", type="array", 
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", description="ID đơn vị"),
     *                         @OA\Property(property="name", type="string", description="Tên đơn vị"),
     *                         @OA\Property(property="active", type="boolean", description="Trạng thái đơn vị")
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
     *             @OA\Property(property="error", type="string", description="Thông báo lỗi"),
     *             @OA\Property(property="message", type="string", description="Mô tả chi tiết lỗi"),
     *             @OA\Property(property="data", type="object", description="Dữ liệu trả về khi lỗi xảy ra")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/admin/product/{id}/update",
     *     summary="Cập nhật thông tin sản phẩm",
     *     description="Cập nhật thông tin sản phẩm bao gồm các trường như tên, giá, mô tả, ảnh, danh mục,...",
     *     tags={"admin/products"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID của sản phẩm cần cập nhật"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="cate_id", type="integer", description="ID danh mục sản phẩm",example=1),
     *                 @OA\Property(property="name", type="string", maxLength=255, description="Tên sản phẩm"),
     *                 @OA\Property(property="price", type="number", format="float", description="Giá sản phẩm"),
     *                 @OA\Property(property="sale", type="integer", description="Giảm giá (%)", example=10),
     *                 @OA\Property(property="img", type="string", format="binary", description="Ảnh sản phẩm"),
     *                 @OA\Property(property="quantity", type="integer", description="Số lượng sản phẩm"),
     *                 @OA\Property(property="description", type="string", description="Mô tả sản phẩm"),
     *                 @OA\Property(property="made", type="string", maxLength=255, description="Nơi sản xuất"),
     *                 @OA\Property(property="active", type="integer", example=1),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật sản phẩm thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", description="ID sản phẩm"),
     *                 @OA\Property(property="name", type="string", description="Tên sản phẩm"),
     *                 @OA\Property(property="price", type="number", format="float", description="Giá sản phẩm"),
     *                 @OA\Property(property="description", type="string", description="Mô tả sản phẩm"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", description="Ngày tạo sản phẩm"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", description="Ngày cập nhật sản phẩm"),
     *                 @OA\Property(property="category", type="object",
     *                     @OA\Property(property="id", type="integer", description="ID danh mục"),
     *                     @OA\Property(property="name", type="string", description="Tên danh mục"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                 ),
     *                 @OA\Property(property="img", type="string", description="URL ảnh sản phẩm"),
     *                 @OA\Property(property="quantity", type="integer", description="Số lượng sản phẩm"),
     *                 @OA\Property(property="active", type="integer", example=1),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sản phẩm không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Thông báo lỗi"),
     *             @OA\Property(property="message", type="string", description="Mô tả chi tiết lỗi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="object", 
     *                 @OA\Property(property="cate_id", type="array", 
     *                     @OA\Items(type="string", description="ID danh mục không hợp lệ")
     *                 ),
     *                 @OA\Property(property="name", type="array", 
     *                     @OA\Items(type="string", description="Tên sản phẩm không hợp lệ")
     *                 ),
     *                 @OA\Property(property="price", type="array", 
     *                     @OA\Items(type="string", description="Giá sản phẩm không hợp lệ")
     *                 ),
     *                 @OA\Property(property="img", type="array", 
     *                     @OA\Items(type="string", description="Định dạng ảnh không hợp lệ")
     *                 ),
     *                 @OA\Property(property="quantity", type="array", 
     *                     @OA\Items(type="string", description="Số lượng không hợp lệ")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Thông báo lỗi"),
     *             @OA\Property(property="message", type="string", description="Mô tả chi tiết lỗi")
     *         )
     *     )
     * )
     */
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
            $dataToUpdate = array_filter($validatedData, fn($value) => !is_null($value));

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

    /**
     * @OA\Get(
     *     path="/api/admin/product/search",
     *     summary="Tìm kiếm sản phẩm",
     *     description="Tìm kiếm sản phẩm theo từ khóa trong tên hoặc mô tả",
     *     tags={"admin/products"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Từ khóa tìm kiếm",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="Laptop"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách sản phẩm tìm thấy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", 
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", description="ID sản phẩm"),
     *                     @OA\Property(property="name", type="string", description="Tên sản phẩm"),
     *                     @OA\Property(property="price", type="number", format="float", description="Giá sản phẩm"),
     *                     @OA\Property(property="description", type="string", description="Mô tả sản phẩm"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", description="Ngày tạo sản phẩm"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", description="Ngày cập nhật sản phẩm")
     *                 )
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Yêu cầu không hợp lệ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Thông báo lỗi"),
     *             @OA\Property(property="message", type="string", description="Mô tả chi tiết lỗi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Thông báo lỗi"),
     *             @OA\Property(property="message", type="string", description="Mô tả chi tiết lỗi")
     *         )
     *     )
     * )
     */
    public function search(Request $request)
    {
        try {
            $inputSearch = $request->input('query');

            $products = Product::withTrashed()->search($inputSearch)->get();

            return $this->sendResponse($products, 'Sản phẩm tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm sản phẩm', ['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/product/{id}/soft-delete",
     *     summary="Xóa mềm sản phẩm",
     *     description="Xóa mềm một sản phẩm theo ID (sản phẩm vẫn tồn tại trong cơ sở dữ liệu nhưng sẽ bị ẩn khỏi kết quả tìm kiếm mặc định).",
     *     tags={"admin/products"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của sản phẩm cần xóa mềm",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xóa mềm sản phẩm thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sản phẩm đã được xóa mềm thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sản phẩm không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy sản phẩm."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Chi tiết lỗi")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi trong quá trình xóa mềm sản phẩm."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Chi tiết lỗi")
     *             )
     *         )
     *     )
     * )
     */
    public function softDelete($id)
    {
        try {
            $product = Product::findOrFail($id);

            // Chuyển trạng thái `active` về 0
            $product->active = 0;
            $product->save();

            // Thực hiện xóa mềm
            $product->delete();

            return $this->sendResponse(null, 'Sản phẩm đã được xóa mềm và chuyển trạng thái active về 0.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy sản phẩm.', ['error' => $th->getMessage()], 404);
        }
    }


    /**
     * @OA\Patch(
     *     path="/api/admin/product/{id}/restore",
     *     summary="Khôi phục sản phẩm đã xóa mềm",
     *     description="Khôi phục một sản phẩm đã bị xóa mềm theo ID.",
     *     tags={"admin/products"},
     *     security={{"bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của sản phẩm cần khôi phục",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Khôi phục sản phẩm thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sản phẩm đã được khôi phục thành công."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Tên sản phẩm"),
     *                 @OA\Property(property="price", type="number", format="float", example=1000.00),
     *                 @OA\Property(property="quantity", type="integer", example=50),
     *                 @OA\Property(property="description", type="string", example="Mô tả sản phẩm"),
     *                 @OA\Property(property="category", type="string", example="Danh mục sản phẩm"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-06T14:52:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-06T15:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy sản phẩm đã xóa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy sản phẩm đã xóa."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Chi tiết lỗi")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi trong quá trình khôi phục sản phẩm."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Chi tiết lỗi")
     *             )
     *         )
     *     )
     * )
     */

    public function restore($id)
    {
        try {
            $product = Product::onlyTrashed()->findOrFail($id);

            // Chuyển trạng thái `active` về 1 khi khôi phục
            $product->active = 1;
            $product->save();

            // Khôi phục sản phẩm
            $product->restore();

            return $this->sendResponse($product, 'Sản phẩm đã được khôi phục thành công.');
        } catch (\Throwable $th) {
            return $this->sendError('Không tìm thấy sản phẩm đã xóa.', ['error' => $th->getMessage()], 404);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/admin/product/create",
     *     summary="Tạo mới sản phẩm",
     *     description="Tạo một sản phẩm mới với các thuộc tính cần thiết.",
     *     tags={"admin/products"},
     *     security={{"bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="multipart/form-data",
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="cate_id", type="integer", description="ID của danh mục", example=1),
     *                     @OA\Property(property="name", type="string", description="Tên sản phẩm", example="Tên sản phẩm mẫu"),
     *                     @OA\Property(property="price", type="number", format="float", description="Giá sản phẩm", example=1000.00),
     *                     @OA\Property(property="sale", type="integer", description="Phần trăm giảm giá", example=10, nullable=true),
     *                     @OA\Property(
     *                         property="img",
     *                         type="string",
     *                         format="binary",
     *                         description="File ảnh sản phẩm"
     *                     ),
     *                     @OA\Property(property="quantity", type="integer", description="Số lượng sản phẩm", example=50),
     *                     @OA\Property(property="description", type="string", description="Mô tả sản phẩm", example="Mô tả chi tiết sản phẩm", nullable=true),
     *                     @OA\Property(property="made", type="string", description="Xuất xứ sản phẩm", example="Xuất xứ sản phẩm", nullable=true),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sản phẩm đã được thêm thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sản phẩm đã được thêm thành công."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="cate_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Tên sản phẩm mẫu"),
     *                 @OA\Property(property="price", type="number", format="float", example=1000.00),
     *                 @OA\Property(property="sale", type="integer", example=10, nullable=true),
     *                 @OA\Property(property="img", type="string", example="URL ảnh sản phẩm"),
     *                 @OA\Property(property="img_public_id", type="string", example="Public ID của ảnh trên Cloudinary"),
     *                 @OA\Property(property="quantity", type="integer", example=50),
     *                 @OA\Property(property="description", type="string", example="Mô tả chi tiết sản phẩm", nullable=true),
     *                 @OA\Property(property="made", type="string", example="Xuất xứ sản phẩm", nullable=true),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-06T14:52:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-06T14:52:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra trong quá trình thêm sản phẩm",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra trong quá trình thêm sản phẩm"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Chi tiết lỗi")
     *             )
     *         )
     *     )
     * )
     */

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

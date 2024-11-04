<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VoucherController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/products/vouchers",
     *     summary="Lấy danh sách voucher hợp lệ",
     *     tags={"product"},
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh sách voucher thành công!",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="DISCOUNT10"),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(property="discount_type", type="string", example="percentage"),
     *                 @OA\Property(property="discount_value", type="number", example=10.5),
     *                 @OA\Property(property="max_discount_value", type="number", example=50),
     *                 @OA\Property(property="description", type="string", example="Giảm giá 10% cho đơn hàng."),
     *                 @OA\Property(property="quantity", type="integer", example=100),
     *                 @OA\Property(property="start_date", type="string", format="date-time", example="2024-11-01T00:00:00Z"),
     *                 @OA\Property(property="end_date", type="string", format="date-time", example="2024-11-30T23:59:59Z"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-01T00:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-01T00:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Danh sách vouchers trống",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Danh sách vouchers trống"),
     *             @OA\Property(property="data", type="string", example=""),
     *             @OA\Property(property="code", type="integer", example=400)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lỗi xảy ra",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Đã có lỗi xảy ra."),
     *             @OA\Property(property="error", type="string", example="Lỗi chi tiết")
     *         )
     *     )
     * )
     */
    public function getVoucher()
    {
        try {

            // Lấy thời gian hiện tại   
            $currentDate = Carbon::now();
            // Lấy các voucher hợp lệ:
            // - Trạng thái phải là 'active'
            // - Ngày hiện tại nằm trong khoảng 'start_date' và 'end_date'
            $vouchers = Voucher::where('active', 1)
                ->where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
                ->get();
            if ($vouchers->isEmpty()) {
                return $this->sendError('Danh sách vouchers trống', '', 400);
            }
            return $this->sendResponse($vouchers, 'Lấy danh sách voucher thành công!');
        } catch (\Throwable $th) {
            return $this->sendError('Đã có lỗi xảy ra.', ['error' => $th->getMessage()], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/products/vouchers/store-user",
     *     summary="Lưu voucher cho người dùng",
     *     tags={"product"},
     *     security={{"bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"voucher_id"},
     *             @OA\Property(property="voucher_id", type="integer", example=1, description="ID của voucher cần lưu")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Voucher đã được lưu thành công cho người dùng.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Voucher đã được lưu thành công cho người dùng."),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Lỗi định dạng hoặc voucher không hợp lệ.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lỗi định dạng"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="code", type="integer", example=400)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Voucher không tồn tại hoặc không tìm thấy.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra khi lưu voucher."),
     *             @OA\Property(property="error", type="string", example="Lỗi chi tiết")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi xảy ra trong quá trình lưu voucher.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra khi lưu voucher."),
     *             @OA\Property(property="error", type="string", example="Lỗi chi tiết")
     *         )
     *     )
     * )
     */
    public function storeUserVoucher(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'voucher_id' => 'required|exists:vouchers,id',
        ]);


        if ($validator->fails()) {
            return $this->sendError('Lỗi định dạng', $validator->errors());
        }

        DB::beginTransaction(); // Bắt đầu giao dịch

        try {
            $user = User::findOrFail(auth()->user()->id);
            $voucher = Voucher::findOrFail($request->voucher_id);

            // Kiểm tra xem người dùng đã có voucher nào của loại này chưa
            $existingVoucher = $user->vouchers()->where('voucher_id', $voucher->id)->first();
            if ($existingVoucher) {
                return $this->sendError('Người dùng đã có voucher này.', [], 400);
            }

            // Kiểm tra số lượng voucher còn lại
            if ($voucher->quantity <= 0) {
                return $this->sendError('Voucher này đã hết.', [], 400);
            }

            // Thêm voucher cho người dùng
            $user->vouchers()->attach($voucher->id);

            // Giảm số lượng của voucher
            $voucher->decrement('quantity');

            DB::commit(); // Cam kết giao dịch

            return $this->sendResponse(null, 'Voucher đã được lưu thành công cho người dùng.');
        } catch (\Throwable $th) {
            DB::rollBack(); // Quay lại nếu có lỗi xảy ra
            return $this->sendError('Có lỗi xảy ra khi lưu voucher.', ['error' => $th->getMessage()], 500);
        }
    }
}

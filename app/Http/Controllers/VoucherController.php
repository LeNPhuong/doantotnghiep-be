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

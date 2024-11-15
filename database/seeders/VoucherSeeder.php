<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $now = Carbon::now();

        $vouchers = [
            [
                'code' => 'DISCOUNT10',
                'active' => 1,
                'discount_type' => 'percentage',
                'discount_value' => 10.00,
                'max_discount_value' => 50.00,
                'description' => 'Giảm giá 10% cho đơn hàng',
                'quantity' => 100,
                'start_date' => $now,
                'end_date' => $now->copy()->addDays(30),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'FLAT100',
                'active' => 1,
                'discount_type' => 'percentage', // Chuyển thành phần trăm
                'discount_value' => 10.00, // Đặt lại giá trị giảm giá, ví dụ 10%
                'max_discount_value' => 100.00, // Có thể đặt giá trị tối đa nếu cần
                'description' => 'Giảm giá 10% cho đơn hàng',
                'quantity' => 50,
                'start_date' => $now,
                'end_date' => $now->copy()->addDays(15),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SUMMER20',
                'active' => 1,
                'discount_type' => 'percentage',
                'discount_value' => 20.00,
                'max_discount_value' => 100.00,
                'description' => 'Giảm giá 20% cho đơn hàng trong mùa hè',
                'quantity' => 200,
                'start_date' => $now,
                'end_date' => $now->copy()->addDays(60),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'WELCOME50',
                'active' => 1,
                'discount_type' => 'percentage', // Chuyển thành phần trăm
                'discount_value' => 5.00, // Đặt lại giá trị giảm giá, ví dụ 5%
                'max_discount_value' => 50.00, // Có thể đặt giá trị tối đa nếu cần
                'description' => 'Giảm giá 5% cho khách hàng mới',
                'quantity' => 30,
                'start_date' => $now,
                'end_date' => $now->copy()->addDays(45),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];


        // Lưu voucher vào cơ sở dữ liệu
        foreach ($vouchers as $voucher) {
            Voucher::create($voucher);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $sampleComments = [
            "Sản phẩm rất tốt, vượt xa mong đợi của tôi!",
            "Giá cả hợp lý, chất lượng ổn định. Rất hài lòng.",
            "Mình đã sử dụng vài tuần và thấy rất đáng tiền.",
            "Sản phẩm có thiết kế đẹp, dễ sử dụng.",
            "Đóng gói cẩn thận, giao hàng nhanh chóng.",
            "Sản phẩm rất tiện lợi cho cuộc sống hàng ngày.",
            "Đã thử và sẽ mua lại lần sau.",
            "Tuy có một vài điểm nhỏ chưa ưng ý nhưng tổng thể vẫn rất tốt.",
            "Được bạn bè giới thiệu và mình rất hài lòng.",
            "Công dụng đúng như mô tả, cảm ơn shop.",
            "Chất lượng hơn cả mong đợi, 5 sao!",
            "Thích nhất là chế độ bảo hành của shop.",
            "Giá rẻ nhưng chất lượng khá ổn. Rất đáng thử.",
            "Dịch vụ khách hàng tốt, tư vấn rất nhiệt tình.",
            "Sản phẩm có độ bền cao, dùng lâu vẫn như mới.",
            "Lần đầu tiên mua hàng online mà hài lòng như vậy.",
            "Rất phù hợp để làm quà tặng cho người thân.",
            "Mẫu mã đẹp, chất lượng tốt. Rất đáng mua.",
            "Có hơi khác so với hình nhưng vẫn rất hài lòng.",
            "Shop phục vụ rất chu đáo, sẽ ủng hộ thêm."
        ];

        $comments = [];

        for ($i = 1; $i <= 20; $i++) {
            $comments[] = [
                'product_id' => $i <= 10 ? 1 : 2, // 10 bình luận đầu cho sản phẩm 1, 10 bình luận sau cho sản phẩm 2
                'user_id' => rand(1, 2), // Giả sử có từ 1 đến 5 người dùng
                'rating' => rand(3, 5), // Đánh giá từ 3 đến 5 sao để tạo cảm giác tích cực
                'comment' => $sampleComments[array_rand($sampleComments)], // Lấy ngẫu nhiên bình luận từ danh sách
                'likes' => rand(0, 50), // Giả sử có từ 0 đến 50 lượt thích
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Chèn dữ liệu vào bảng comments
        DB::table('comments')->insert($comments);
    }
}

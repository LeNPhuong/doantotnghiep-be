<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('product')->onDelete('cascade'); // Liên kết với bảng sản phẩm
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Liên kết với bảng người dùng
            $table->unsignedTinyInteger('rating')->default(0); // Đánh giá từ 1 đến 5 sao
            $table->text('comment'); // Nội dung bình luận
            $table->unsignedInteger('likes')->default(0); // Số lượt thích
            $table->timestamps(); // Tạo trường create_at và update_at tự động
            $table->softDeletes();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};

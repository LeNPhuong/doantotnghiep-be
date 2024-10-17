<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product', function (Blueprint $table) {
            $table->id(); // ID của sản phẩm
            $table->foreignId(column: 'cate_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('name'); // Tên sản phẩm
            $table->decimal('price', 10, 2); // Giá sản phẩm (số thập phân)
            $table->integer('sale')->unsigned()->default(0); // Sale (phần trăm giảm giá, không lớn hơn 100 và không nhỏ hơn 0)
            $table->string('img'); // Link ảnh sản phẩm
            $table->string('type'); // Loại sản phẩm
            $table->text('description'); // Mô tả sản phẩm
            $table->string('made'); // Nơi sản xuất
            $table->timestamps(); // Tạo hai trường create_at và update_at tự động
            $table->boolean('active')->default(true); // Trạng thái active (true/false)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_product');
    }
};

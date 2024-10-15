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
        Schema::create('order_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId(column: 'order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId(column: 'product_id')->nullable()->constrained('product')->onDelete('set null');
            $table->integer('quantity');
            $table->float('price');
            $table->float('price_after_sale');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_order_detail');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('store_id');
            $table->string('location', 120)->nullable();
            $table->decimal('cost', 12, 2)->default(0.00);
            $table->decimal('margin_percent', 6, 2)->default(0.00);
            $table->decimal('price', 12, 2)->default(0.00);
            $table->timestamps();
            
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->unique(['product_id', 'store_id'], 'uq_price_product_store');
            $table->index('store_id');
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};


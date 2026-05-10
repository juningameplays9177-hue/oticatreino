<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->enum('type', ['PRODUTO', 'SERVICO'])->default('PRODUTO');
            $table->string('ref', 50)->nullable();
            $table->string('name', 190);
            $table->string('unit', 10)->default('UN');
            $table->decimal('qty', 10, 3)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0.00);
            $table->decimal('price_adjust', 12, 2)->default(0.00);
            $table->decimal('unit_price_net', 12, 2)->default(0.00);
            $table->decimal('add_disc_percent', 6, 2)->default(0.00);
            $table->decimal('line_total', 12, 2)->default(0.00);
            $table->string('barcode', 20)->nullable();
            $table->timestamps();
            
            $table->foreign('service_order_id')->references('id')->on('service_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            
            $table->index('service_order_id');
            $table->index('product_id');
            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_items');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_order_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_order_id');
            $table->string('path', 255);
            $table->unsignedTinyInteger('position')->default(1);
            $table->timestamps();
            
            $table->foreign('service_order_id')->references('id')->on('service_orders')->onDelete('cascade');
            $table->unique(['service_order_id', 'position']);
            $table->index('service_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_images');
    }
};


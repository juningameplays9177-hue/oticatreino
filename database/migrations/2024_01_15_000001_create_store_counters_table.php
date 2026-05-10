<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->year('year');
            $table->unsignedInteger('current_os')->default(0);
            $table->timestamps();
            
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->unique(['store_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_counters');
    }
};


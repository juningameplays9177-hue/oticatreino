<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name', 190);
            $table->string('trade_name', 190)->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('phone', 30)->nullable();
            $table->timestamps();
            
            $table->index('cnpj');
            $table->index(['legal_name', 'trade_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};


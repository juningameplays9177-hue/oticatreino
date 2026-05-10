<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela de Cores
        if (!Schema::hasTable('product_colors')) {
            Schema::create('product_colors', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('hex_code', 7)->nullable(); // Para cores hex
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Tabela de Tamanhos
        if (!Schema::hasTable('product_sizes')) {
            Schema::create('product_sizes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50)->unique();
                $table->integer('order')->default(0); // Para ordenação
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Tabela de Formatos
        if (!Schema::hasTable('product_shapes')) {
            Schema::create('product_shapes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_shapes');
        Schema::dropIfExists('product_sizes');
        Schema::dropIfExists('product_colors');
    }
};


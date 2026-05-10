<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code_prefix', 10)->unique(); // P, L, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Adicionar campo product_type_id na tabela products
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'product_type_id')) {
                $table->unsignedBigInteger('product_type_id')->nullable()->after('id');
                $table->foreign('product_type_id')->references('id')->on('product_types')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'product_type_id')) {
                $table->dropForeign(['product_type_id']);
                $table->dropColumn('product_type_id');
            }
        });
        
        Schema::dropIfExists('product_types');
    }
};


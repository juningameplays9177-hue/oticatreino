<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 50)->unique()->nullable();
            $table->string('ean13', 13)->unique()->nullable();
            $table->string('name', 190);
            $table->enum('unit', ['FR', 'KIT', 'PAR', 'PC', 'UN'])->default('UN');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('subgroup_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('color', 60)->nullable();
            $table->string('size', 60)->nullable();
            $table->string('shape', 60)->nullable();
            $table->boolean('sell_only_with_os')->default(false);
            $table->boolean('control_stock')->default(true);
            $table->boolean('showcase_enabled')->default(false);
            $table->boolean('archived')->default(false);
            $table->mediumText('description')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('label_code')->unique()->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
            $table->foreign('group_id')->references('id')->on('product_groups')->onDelete('set null');
            $table->foreign('subgroup_id')->references('id')->on('product_subgroups')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            
            // Indexes
            $table->index(['name', 'ref', 'ean13'], 'idx_products_search');
            $table->index(['brand_id', 'group_id', 'subgroup_id'], 'idx_products_brand_group');
            $table->index('archived', 'idx_products_archived');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};


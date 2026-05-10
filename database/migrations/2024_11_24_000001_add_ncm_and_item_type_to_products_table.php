<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Adicionar NCM (Nomenclatura Comum do Mercosul)
            if (!Schema::hasColumn('products', 'ncm')) {
                $table->string('ncm', 8)->nullable()->after('ean13');
            }
            
            // Adicionar tipo do item (pode ser usado para classificação fiscal)
            if (!Schema::hasColumn('products', 'item_type')) {
                $table->string('item_type', 60)->nullable()->after('ncm');
            }
            
            // Adicionar índice para NCM se não existir
            if (!Schema::hasIndex('products', 'idx_products_ncm')) {
                $table->index('ncm', 'idx_products_ncm');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasIndex('products', 'idx_products_ncm')) {
                $table->dropIndex('idx_products_ncm');
            }
            
            if (Schema::hasColumn('products', 'item_type')) {
                $table->dropColumn('item_type');
            }
            
            if (Schema::hasColumn('products', 'ncm')) {
                $table->dropColumn('ncm');
            }
        });
    }
};


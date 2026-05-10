<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('suppliers')) {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            
            // Primeiro, remover foreign keys que usam company_id
            $foreignKeys = $connection->select(
                "SELECT CONSTRAINT_NAME 
                 FROM information_schema.KEY_COLUMN_USAGE 
                 WHERE TABLE_SCHEMA = ? 
                 AND TABLE_NAME = 'suppliers' 
                 AND COLUMN_NAME = 'company_id' 
                 AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$databaseName]
            );
            
            foreach ($foreignKeys as $fk) {
                try {
                    Schema::table('suppliers', function (Blueprint $table) use ($fk) {
                        $table->dropForeign($fk->CONSTRAINT_NAME);
                    });
                } catch (\Exception $e) {
                    // Foreign key pode não existir, continuar
                }
            }
            
            // Depois, remover índices que dependem de company_id
            // Verificar se o índice existe antes de tentar remover
            $indexes = $connection->select(
                "SELECT INDEX_NAME 
                 FROM information_schema.STATISTICS 
                 WHERE TABLE_SCHEMA = ? 
                 AND TABLE_NAME = 'suppliers' 
                 AND INDEX_NAME LIKE '%company%'",
                [$databaseName]
            );
            
            foreach ($indexes as $index) {
                try {
                    Schema::table('suppliers', function (Blueprint $table) use ($index) {
                        $table->dropIndex($index->INDEX_NAME);
                    });
                } catch (\Exception $e) {
                    // Índice pode não existir, continuar
                }
            }
            
            // Por último, remover a coluna company_id
            if (Schema::hasColumn('suppliers', 'company_id')) {
                Schema::table('suppliers', function (Blueprint $table) {
                    $table->dropColumn('company_id');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (!Schema::hasColumn('suppliers', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                    
                    // Adicionar foreign key se a tabela companies existir
                    if (Schema::hasTable('companies')) {
                        $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                    }
                    
                    // Adicionar índice
                    $table->index(['company_id', 'is_active'], 'idx_suppliers_company_active');
                }
            });
        }
    }
};


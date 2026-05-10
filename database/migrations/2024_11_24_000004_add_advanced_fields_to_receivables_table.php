<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('receivables')) {
            Schema::table('receivables', function (Blueprint $table) {
                // Categorias e Centro de Custo
                if (!Schema::hasColumn('receivables', 'category_id')) {
                    $table->foreignId('category_id')->nullable()->after('status')->constrained('finance_categories')->onDelete('set null');
                }
                if (!Schema::hasColumn('receivables', 'cost_center_id')) {
                    $table->foreignId('cost_center_id')->nullable()->after('category_id')->constrained('cost_centers')->onDelete('set null');
                }
                
                // Valores financeiros adicionais
                if (!Schema::hasColumn('receivables', 'interest_amount')) {
                    $table->decimal('interest_amount', 10, 2)->default(0)->after('balance_amount');
                }
                if (!Schema::hasColumn('receivables', 'fine_amount')) {
                    $table->decimal('fine_amount', 10, 2)->default(0)->after('interest_amount');
                }
                if (!Schema::hasColumn('receivables', 'discount_amount')) {
                    $table->decimal('discount_amount', 10, 2)->default(0)->after('fine_amount');
                }
                
                // Tipo de cobrança
                if (!Schema::hasColumn('receivables', 'billing_type')) {
                    $table->string('billing_type', 50)->nullable()->after('method');
                }
                
                // Parcelas
                if (!Schema::hasColumn('receivables', 'installments')) {
                    $table->integer('installments')->default(1)->after('billing_type');
                }
                if (!Schema::hasColumn('receivables', 'installment_number')) {
                    $table->integer('installment_number')->default(1)->after('installments');
                }
                if (!Schema::hasColumn('receivables', 'parent_receivable_id')) {
                    $table->foreignId('parent_receivable_id')->nullable()->after('installment_number')->constrained('receivables')->onDelete('cascade');
                }
                if (!Schema::hasColumn('receivables', 'renegotiated_from_id')) {
                    $table->foreignId('renegotiated_from_id')->nullable()->after('parent_receivable_id')->constrained('receivables')->onDelete('set null');
                }
                
                // Documento/Número do título
                if (!Schema::hasColumn('receivables', 'document_no')) {
                    $table->string('document_no', 50)->nullable()->after('our_number');
                }
                
                // Observações
                if (!Schema::hasColumn('receivables', 'note')) {
                    $table->text('note')->nullable()->after('document_no');
                }
                
                // Anexos
                if (!Schema::hasColumn('receivables', 'attachment_path')) {
                    $table->string('attachment_path')->nullable()->after('note');
                }
                
                // Status expandido
                if (!Schema::hasColumn('receivables', 'status')) {
                    // Se a coluna não existe, criar
                    $table->string('status', 20)->default('open')->after('balance_amount');
                } else {
                    // Se existe, verificar se precisa alterar
                    // Não vamos alterar, apenas garantir que aceita os novos valores
                }
            });
            
            // Adicionar índices
            Schema::table('receivables', function (Blueprint $table) {
                if (!$this->hasIndex('receivables', 'idx_receivables_category')) {
                    $table->index('category_id', 'idx_receivables_category');
                }
                if (!$this->hasIndex('receivables', 'idx_receivables_billing_type')) {
                    $table->index('billing_type', 'idx_receivables_billing_type');
                }
                if (!$this->hasIndex('receivables', 'idx_receivables_document_no')) {
                    $table->index('document_no', 'idx_receivables_document_no');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('receivables')) {
            Schema::table('receivables', function (Blueprint $table) {
                $columns = [
                    'attachment_path',
                    'note',
                    'document_no',
                    'renegotiated_from_id',
                    'parent_receivable_id',
                    'installment_number',
                    'installments',
                    'billing_type',
                    'discount_amount',
                    'fine_amount',
                    'interest_amount',
                    'cost_center_id',
                    'category_id',
                ];
                
                foreach ($columns as $column) {
                    if (Schema::hasColumn('receivables', $column)) {
                        if (in_array($column, ['category_id', 'cost_center_id', 'parent_receivable_id', 'renegotiated_from_id'])) {
                            $table->dropForeign(['receivables_' . $column . '_foreign']);
                        }
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
    
    /**
     * Verifica se um índice existe
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = $connection->select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$databaseName, $table, $indexName]
        );
        
        return $result[0]->count > 0;
    }
};


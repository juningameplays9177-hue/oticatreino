<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A tabela suppliers já existe, apenas atualizar adicionando colunas que faltam
        if (!Schema::hasTable('suppliers')) {
            // Se por algum motivo não existir, criar do zero
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1);
                $table->enum('tax_id_type', ['CPF', 'CNPJ'])->default('CNPJ');
                $table->char('cpf_clean', 11)->nullable();
                $table->char('cnpj_clean', 14)->nullable();
                $table->string('trade_name', 190);
                $table->string('legal_name', 190)->nullable();
                $table->boolean('is_lab')->default(false);
                $table->boolean('taxpayer_icms')->default(false);
                $table->string('ie', 40)->nullable();
                $table->string('im', 40)->nullable();
                $table->string('suframa', 40)->nullable();
                $table->string('email', 190)->nullable();
                $table->string('website', 190)->nullable();
                $table->char('zip_code', 8)->nullable();
                $table->string('address', 190)->nullable();
                $table->string('number', 30)->nullable();
                $table->string('complement', 120)->nullable();
                $table->string('district', 120)->nullable();
                $table->string('city', 120)->nullable();
                $table->char('state', 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->index(['company_id', 'is_active'], 'idx_suppliers_company_active');
                $table->index('cnpj_clean', 'idx_suppliers_cnpj');
                $table->index('cpf_clean', 'idx_suppliers_cpf');
            });
            return;
        }

        // Se já existe, adicionar colunas que faltam
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'company_id')) {
                $table->unsignedBigInteger('company_id')->after('id')->default(1);
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('suppliers', 'tax_id_type')) {
                $table->enum('tax_id_type', ['CPF', 'CNPJ'])->default('CNPJ')->after('company_id');
            }
            if (!Schema::hasColumn('suppliers', 'cpf_clean')) {
                $table->char('cpf_clean', 11)->nullable()->after('tax_id_type');
            }
            if (!Schema::hasColumn('suppliers', 'cnpj_clean')) {
                $table->char('cnpj_clean', 14)->nullable()->after('cpf_clean');
            }
            
            // Ajustar campos existentes
            if (Schema::hasColumn('suppliers', 'trade_name')) {
                $table->string('trade_name', 190)->nullable()->change();
            }
            if (Schema::hasColumn('suppliers', 'legal_name')) {
                $table->string('legal_name', 190)->nullable()->change();
            }
            if (Schema::hasColumn('suppliers', 'email')) {
                $table->string('email', 190)->nullable()->change();
            }
            
            // Novos campos
            $newColumns = [
                'is_lab' => ['type' => 'boolean', 'default' => false, 'after' => 'legal_name'],
                'taxpayer_icms' => ['type' => 'boolean', 'default' => false, 'after' => 'is_lab'],
                'ie' => ['type' => 'string', 'length' => 40, 'nullable' => true, 'after' => 'taxpayer_icms'],
                'im' => ['type' => 'string', 'length' => 40, 'nullable' => true, 'after' => 'ie'],
                'suframa' => ['type' => 'string', 'length' => 40, 'nullable' => true, 'after' => 'im'],
                'website' => ['type' => 'string', 'length' => 190, 'nullable' => true, 'after' => 'email'],
                'zip_code' => ['type' => 'char', 'length' => 8, 'nullable' => true, 'after' => 'website'],
                'address' => ['type' => 'string', 'length' => 190, 'nullable' => true, 'after' => 'zip_code'],
                'number' => ['type' => 'string', 'length' => 30, 'nullable' => true, 'after' => 'address'],
                'complement' => ['type' => 'string', 'length' => 120, 'nullable' => true, 'after' => 'number'],
                'district' => ['type' => 'string', 'length' => 120, 'nullable' => true, 'after' => 'complement'],
                'city' => ['type' => 'string', 'length' => 120, 'nullable' => true, 'after' => 'district'],
                'state' => ['type' => 'char', 'length' => 2, 'nullable' => true, 'after' => 'city'],
                'is_active' => ['type' => 'boolean', 'default' => true, 'after' => 'state'],
                'notes' => ['type' => 'text', 'nullable' => true, 'after' => 'is_active'],
            ];
            
            foreach ($newColumns as $column => $config) {
                if (!Schema::hasColumn('suppliers', $column)) {
                    if ($config['type'] === 'boolean') {
                        $table->boolean($column)->default($config['default'])->after($config['after']);
                    } elseif ($config['type'] === 'char') {
                        $table->char($column, $config['length'])->nullable()->after($config['after']);
                    } elseif ($config['type'] === 'string') {
                        $table->string($column, $config['length'])->nullable()->after($config['after']);
                    } elseif ($config['type'] === 'text') {
                        $table->text($column)->nullable()->after($config['after']);
                    }
                }
            }
        });
        
        // Adicionar índices se as colunas existirem
        $this->addIndexIfNotExists('suppliers', ['company_id', 'is_active'], 'idx_suppliers_company_active');
        $this->addIndexIfNotExists('suppliers', 'cnpj_clean', 'idx_suppliers_cnpj');
        $this->addIndexIfNotExists('suppliers', 'cpf_clean', 'idx_suppliers_cpf');
    }

    public function down(): void
    {
        // Não fazer nada no down para evitar perda de dados
        // A migration anterior já tem o drop da tabela
    }
    
    private function addIndexIfNotExists(string $table, $columns, string $indexName): void
    {
        $columnsArray = is_array($columns) ? $columns : [$columns];
        
        // Verificar se todas as colunas existem
        foreach ($columnsArray as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return; // Se alguma coluna não existe, não criar o índice
            }
        }
        
        // Verificar se o índice já existe
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $indexName]
        );
        
        if ($result[0]->count == 0) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                if (is_array($columns)) {
                    $table->index($columns, $indexName);
                } else {
                    $table->index($columns, $indexName);
                }
            });
        }
    }
};

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Client;

class ClientDiagnosticController extends Controller
{
    public function test()
    {
        $diagnostics = [];
        
        // 1. Verificar se a tabela existe
        $diagnostics['table_exists'] = Schema::hasTable('clients');
        
        // 2. Verificar colunas importantes
        if ($diagnostics['table_exists']) {
            $diagnostics['columns'] = [
                'id' => Schema::hasColumn('clients', 'id'),
                'name' => Schema::hasColumn('clients', 'name'),
                'code' => Schema::hasColumn('clients', 'code'),
                'active' => Schema::hasColumn('clients', 'active'),
                'cpf_cnpj' => Schema::hasColumn('clients', 'cpf_cnpj'),
            ];
        }
        
        // 3. Verificar AUTO_INCREMENT
        if ($diagnostics['table_exists']) {
            try {
                $result = DB::select("SHOW TABLE STATUS LIKE 'clients'");
                if (!empty($result)) {
                    $diagnostics['auto_increment'] = $result[0]->Auto_increment ?? 'N/A';
                }
            } catch (\Exception $e) {
                $diagnostics['auto_increment_error'] = $e->getMessage();
            }
        }
        
        // 4. Contar registros
        if ($diagnostics['table_exists']) {
            try {
                $diagnostics['total_clients'] = Client::count();
            } catch (\Exception $e) {
                $diagnostics['count_error'] = $e->getMessage();
            }
        }
        
        // 5. Testar criação de cliente (sem salvar)
        try {
            $testClient = new Client();
            $testClient->name = 'TESTE';
            $testClient->active = true;
            
            // Verificar se pode atribuir valores
            $diagnostics['can_assign_values'] = true;
            
            // Verificar fillable
            $diagnostics['fillable'] = $testClient->getFillable();
            
        } catch (\Exception $e) {
            $diagnostics['test_creation_error'] = $e->getMessage();
        }
        
        // 6. Verificar se pode gerar código
        try {
            if (Schema::hasColumn('clients', 'code')) {
                $diagnostics['can_generate_code'] = true;
                $testCode = Client::generateCode();
                $diagnostics['generated_code'] = $testCode;
            } else {
                $diagnostics['can_generate_code'] = false;
                $diagnostics['code_column_missing'] = true;
            }
        } catch (\Exception $e) {
            $diagnostics['generate_code_error'] = $e->getMessage();
        }
        
        // 7. Verificar permissões de escrita
        try {
            $diagnostics['database_writable'] = true;
        } catch (\Exception $e) {
            $diagnostics['database_writable'] = false;
            $diagnostics['database_error'] = $e->getMessage();
        }
        
        return response()->json($diagnostics, 200, [], JSON_PRETTY_PRINT);
    }
}

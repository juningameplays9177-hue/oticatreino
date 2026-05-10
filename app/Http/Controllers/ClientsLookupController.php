<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ClientsLookupController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->get('q', '');

            if (empty($search) || strlen(trim($search)) < 1) {
                return response()->json([]);
            }
            
            // Aceitar busca com pelo menos 1 caractere (útil para CPF/CNPJ)

            $search = trim($search);
            $normalized = preg_replace('/[^0-9]/', '', $search);
            
            Log::info('Buscando clientes', [
                'search' => $search,
                'normalized' => $normalized
            ]);
            
            // Buscar clientes
            $query = DB::table('clients')
                ->where(function($q) use ($search, $normalized) {
                    $q->where('name', 'like', '%' . $search . '%');
                    // Buscar por código se a coluna existir
                    if (DB::getSchemaBuilder()->hasColumn('clients', 'code')) {
                        $q->orWhere('code', 'like', '%' . $search . '%');
                    }
                    if (!empty($normalized)) {
                        $q->orWhere('cpf_cnpj', 'like', '%' . $normalized . '%');
                    }
                });
            
            // Filtrar apenas clientes ativos se a coluna existir
            if (DB::getSchemaBuilder()->hasColumn('clients', 'active')) {
                $query->where('active', 1);
            }
            
            $query->limit(20);

            // Selecionar campos incluindo code se existir
            $columns = ['id', 'name', 'cpf_cnpj'];
            if (DB::getSchemaBuilder()->hasColumn('clients', 'code')) {
                $columns[] = 'code';
            }
            $clients = $query->get($columns);
            
            Log::info('Clientes encontrados', [
                'count' => $clients->count(),
                'first' => $clients->first()
            ]);

            $results = [];
            foreach ($clients as $client) {
                $name = (string) ($client->name ?? 'Sem nome');
                $cpfCnpj = (string) ($client->cpf_cnpj ?? '');
                $code = isset($client->code) ? (string) $client->code : '';
                
                // Montar texto de exibição
                $textParts = [];
                if ($code) {
                    $textParts[] = $code;
                }
                $textParts[] = $name;
                if ($cpfCnpj) {
                    $textParts[] = '(' . $cpfCnpj . ')';
                }
                
                $results[] = [
                    'id' => (int) $client->id,
                    'name' => $name,
                    'code' => $code,
                    'cpf_cnpj' => $cpfCnpj,
                    'text' => implode(' ', $textParts),
                ];
            }

            return response()->json($results);
        } catch (\Throwable $e) {
            Log::error('Erro em ClientsLookupController', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([]);
        }
    }
}

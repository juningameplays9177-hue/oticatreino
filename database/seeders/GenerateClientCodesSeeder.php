<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class GenerateClientCodesSeeder extends Seeder
{
    /**
     * Gera códigos para clientes que não têm código
     */
    public function run(): void
    {
        $this->command->info('Gerando códigos para clientes sem código...');
        
        // Buscar clientes sem código
        $clientsWithoutCode = Client::whereNull('code')
            ->orWhere('code', '')
            ->orderBy('id')
            ->get();
        
        if ($clientsWithoutCode->isEmpty()) {
            $this->command->info('Todos os clientes já possuem código.');
            return;
        }
        
        // Buscar o último código gerado
        $lastClient = Client::whereNotNull('code')
            ->where('code', 'like', 'CLI-%')
            ->orderByRaw('CAST(SUBSTRING(code, 5) AS UNSIGNED) DESC')
            ->first();
        
        $nextNumber = 1;
        if ($lastClient && $lastClient->code) {
            $lastNumber = (int) substr($lastClient->code, 4);
            $nextNumber = $lastNumber + 1;
        }
        
        $count = 0;
        foreach ($clientsWithoutCode as $client) {
            $code = 'CLI-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            // Verificar se o código já existe (por segurança)
            while (Client::where('code', $code)->exists()) {
                $nextNumber++;
                $code = 'CLI-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
            
            $client->update(['code' => $code]);
            $count++;
            $nextNumber++;
        }
        
        $this->command->info("✓ Códigos gerados para {$count} cliente(s).");
    }
}


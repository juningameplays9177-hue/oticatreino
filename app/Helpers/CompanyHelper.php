<?php

namespace App\Helpers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CompanyHelper
{
    /**
     * Obtém ou cria uma empresa padrão para o usuário
     * 
     * @param User|null $user
     * @return Company
     */
    public static function getOrCreateDefaultCompany(?User $user = null): Company
    {
        if (!$user) {
            $user = auth()->user();
        }

        // Se o usuário tem company_id, usar essa empresa
        if ($user && $user->company_id) {
            $company = Company::find($user->company_id);
            if ($company) {
                return $company;
            }
        }

        // Buscar primeira empresa ativa
        $company = Company::where('is_active', true)->first();
        if ($company) {
            return $company;
        }

        // Criar empresa padrão se não existir nenhuma
        try {
            $companyData = [
                'trade_name' => 'Hospital dos Óculos',
                'legal_name' => 'Hospital dos Óculos',
                'slug' => 'hospital-dos-oculos',
                'is_active' => true,
            ];
            
            // Adicionar tax_id apenas se a coluna existir
            if (\Schema::hasColumn('companies', 'tax_id')) {
                $companyData['tax_id'] = '00000000000000';
            }
            
            $company = Company::create($companyData);

            Log::info('Empresa padrão criada automaticamente', ['company_id' => $company->id]);

            // Atualizar usuário admin se existir
            if ($user && $user->isAdmin()) {
                $user->update(['company_id' => $company->id]);
            }

            return $company;
        } catch (\Exception $e) {
            Log::error('Erro ao criar empresa padrão: ' . $e->getMessage());
            throw new \Exception('Não foi possível criar empresa padrão. Por favor, crie uma empresa manualmente.');
        }
    }

    /**
     * Obtém o ID da empresa do usuário ou cria uma padrão
     * 
     * @param User|null $user
     * @return int
     */
    public static function getCompanyId(?User $user = null): int
    {
        $company = self::getOrCreateDefaultCompany($user);
        return $company->id;
    }
}


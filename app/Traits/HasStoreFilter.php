<?php

namespace App\Traits;

trait HasStoreFilter
{
    /**
     * Obtém o ID da loja do usuário logado
     * Se for admin, retorna null (sem filtro)
     * Se for gerente, retorna o store_id do usuário
     */
    protected function getUserStoreId(): ?int
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }
        
        // Admin pode ver todas as lojas (retorna null)
        if ($user->isAdmin()) {
            return null;
        }
        
        // Gerente só vê sua loja
        return $user->store_id;
    }
    
    /**
     * Aplica filtro de loja na query se o usuário for gerente
     */
    protected function applyStoreFilter($query, $storeColumn = 'store_id')
    {
        $storeId = $this->getUserStoreId();
        
        if ($storeId !== null) {
            $query->where($storeColumn, $storeId);
        }
        
        return $query;
    }
}


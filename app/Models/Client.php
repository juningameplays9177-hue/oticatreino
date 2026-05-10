<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'active',
        'name',
        'cpf_cnpj',
        'birth_date',
        'cep',
        'city',
        'district',
        'address',
        'number',
        'complement',
        'state',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
        'birth_date' => 'date',
        'default_adjust_percent' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com telefones
     */
    public function phones(): HasMany
    {
        return $this->hasMany(ClientPhone::class);
    }

    /**
     * Relacionamento com e-mails
     */
    public function emails(): HasMany
    {
        return $this->hasMany(ClientEmail::class);
    }
    
    /**
     * Relacionamento com vendas
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }
    
    /**
     * Relacionamento com ordens de serviço
     */
    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class, 'client_id');
    }
    
    /**
     * Relacionamento com contas a receber
     */
    public function receivables(): HasMany
    {
        return $this->hasMany(\App\Models\Finance\Receivable::class, 'customer_id');
    }

    /**
     * Scope para busca livre
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        $searchTerm = '%' . $search . '%';

        return $query->where(function ($q) use ($searchTerm, $search) {
            $q->where('name', 'like', $searchTerm)
                ->orWhere('cpf_cnpj', 'like', $searchTerm)
                ->orWhereExists(function ($subQuery) use ($searchTerm) {
                    $subQuery->select(DB::raw(1))
                        ->from('client_emails')
                        ->whereColumn('client_emails.client_id', 'clients.id')
                        ->where('client_emails.email', 'like', $searchTerm);
                })
                ->orWhereExists(function ($subQuery) use ($searchTerm) {
                    $subQuery->select(DB::raw(1))
                        ->from('client_phones')
                        ->whereColumn('client_phones.client_id', 'clients.id')
                        ->where('client_phones.phone', 'like', $searchTerm);
                });
        });
    }

    /**
     * Scope para filtros
     */
    public function scopeFilters($query, $filters)
    {
        if (isset($filters['active']) && $filters['active'] !== '') {
            $query->where('active', $filters['active']);
        }

        if (isset($filters['city']) && !empty($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

        if (isset($filters['district']) && !empty($filters['district'])) {
            $query->where('district', 'like', '%' . $filters['district'] . '%');
        }

        if (isset($filters['from']) && !empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (isset($filters['to']) && !empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query;
    }

    /**
     * Normalizar CPF/CNPJ (remover caracteres não numéricos)
     */
    public static function normalizeCpfCnpj($value)
    {
        if (empty($value)) {
            return null;
        }
        return preg_replace('/\D/', '', $value);
    }

    /**
     * Validar CPF
     */
    public static function validateCpf($cpf)
    {
        $cpf = self::normalizeCpfCnpj($cpf);
        
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validar CNPJ
     */
    public static function validateCnpj($cnpj)
    {
        $cnpj = self::normalizeCpfCnpj($cnpj);
        
        if (strlen($cnpj) != 14) {
            return false;
        }

        $length = strlen($cnpj) - 2;
        $numbers = substr($cnpj, 0, $length);
        $digits = substr($cnpj, $length);
        $sum = 0;
        $pos = $length - 7;

        for ($i = $length; $i >= 1; $i--) {
            $sum += $numbers[$length - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }

        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;
        if ($result != $digits[0]) {
            return false;
        }

        $length = $length + 1;
        $numbers = substr($cnpj, 0, $length);
        $sum = 0;
        $pos = $length - 7;

        for ($i = $length; $i >= 1; $i--) {
            $sum += $numbers[$length - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }

        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;
        if ($result != $digits[1]) {
            return false;
        }

        return true;
    }

    /**
     * Gera um código sequencial para o cliente
     * Formato: CLI-0001, CLI-0002, etc.
     * 
     * @return string
     */
    public static function generateCode(): string
    {
        try {
            // Buscar o último código gerado
            $lastClient = self::whereNotNull('code')
                ->where('code', 'like', 'CLI-%')
                ->orderByRaw('CAST(SUBSTRING(code, 5) AS UNSIGNED) DESC')
                ->first();

            if ($lastClient && $lastClient->code) {
                // Extrair o número do último código
                $lastNumber = (int) substr($lastClient->code, 4);
                $nextNumber = $lastNumber + 1;
            } else {
                // Primeiro cliente
                $nextNumber = 1;
            }

            // Formatar com zeros à esquerda (4 dígitos)
            return 'CLI-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            // Em caso de erro, retornar código baseado no ID atual + 1
            // ou simplesmente retornar um código padrão
            \Log::warning('Erro ao gerar código do cliente: ' . $e->getMessage());
            $count = self::count();
            return 'CLI-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Boot method para gerar código automaticamente ao criar
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($client) {
            \Log::info('Client::creating - Evento disparado', [
                'name' => $client->name ?? 'N/A',
                'has_code' => !empty($client->code),
            ]);
            
            // Definir created_at usando a data de trabalho se disponível
            if (!$client->created_at) {
                try {
                    $workDate = \App\Helpers\WorkDateHelper::getWorkDate();
                    $client->created_at = $workDate;
                    \Log::info('Client::creating - Data de criação definida com data de trabalho', [
                        'created_at' => $workDate->format('Y-m-d H:i:s'),
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Erro ao definir data de trabalho no cliente: ' . $e->getMessage());
                    // Se houver erro, deixar o Laravel definir automaticamente
                }
            }
            
            // Verificar se a coluna 'code' existe antes de tentar gerar
            if (Schema::hasColumn('clients', 'code')) {
                if (empty($client->code)) {
                    try {
                        \Log::info('Client::creating - Gerando código');
                        $client->code = self::generateCode();
                        \Log::info('Client::creating - Código gerado: ' . $client->code);
                    } catch (\Exception $e) {
                        // Se houver erro ao gerar código, deixar null (se permitido)
                        \Log::warning('Erro ao gerar código do cliente no boot: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                        ]);
                        // Não definir code se houver erro, deixar null
                    }
                } else {
                    \Log::info('Client::creating - Código já definido: ' . $client->code);
                }
            } else {
                \Log::info('Client::creating - Coluna code não existe na tabela');
            }
        });
        
        static::created(function ($client) {
            \Log::info('Client::created - Cliente criado no banco', [
                'id' => $client->id,
                'name' => $client->name,
                'code' => $client->code ?? 'N/A',
            ]);
        });
    }
}

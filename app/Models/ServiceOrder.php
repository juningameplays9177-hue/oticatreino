<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class ServiceOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'os_number',
        'company_id',
        'store_id',
        'os_type',
        'registered_at',
        'employee_id',
        'client_id',
        'source',
        'delivery_date',
        'delivery_time',
        'notes',
        'status',
        'advance_type',
        'advance_value',
        'sinal_amount',
        'sinal_method',
        'subtotal',
        'discount_value',
        'total_value',
        'cancel_reason',
        'loss_reason',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'delivery_date' => 'date',
        'delivery_time' => 'datetime',
        'advance_value' => 'decimal:2',
        'sinal_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ServiceOrderImage::class)->orderBy('position');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ServiceOrderStatusHistory::class)->orderBy('changed_at', 'desc');
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(ServiceOrderPrescription::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(\App\Models\Sale::class);
    }

    // Scopes
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        $searchTerm = '%' . $search . '%';

        return $query->where(function ($q) use ($searchTerm, $search) {
            $q->where('os_number', 'like', $searchTerm)
                ->orWhereHas('client', function ($clientQuery) use ($searchTerm, $search) {
                    $clientQuery->where('name', 'like', $searchTerm)
                        ->orWhere('nickname', 'like', $searchTerm)
                        ->orWhere('cpf_cnpj', 'like', preg_replace('/[^0-9]/', '', $search));
                });
        });
    }

    public function scopeFilters($query, $filters)
    {
        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['advance_type'])) {
            $query->where('advance_type', $filters['advance_type']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', 'like', '%' . $filters['source'] . '%');
        }

        if (!empty($filters['from']) && !empty($filters['to'])) {
            $periodType = $filters['period_type'] ?? 'registered';
            switch ($periodType) {
                case 'delivery':
                    $query->whereBetween('delivery_date', [$filters['from'], $filters['to']]);
                    break;
                default:
                    $query->whereBetween('registered_at', [$filters['from'], $filters['to']]);
            }
        }

        if (!empty($filters['min_value'])) {
            $query->where('total_value', '>=', $filters['min_value']);
        }

        if (!empty($filters['max_value'])) {
            $query->where('total_value', '<=', $filters['max_value']);
        }

        return $query;
    }

    // Helper methods
    /**
     * Retorna o próximo número da OS sem incrementar (apenas para visualização)
     */
    public static function getNextOsNumber($storeId, $isConserto = false)
    {
        try {
            // Validar storeId
            if (!$storeId) {
                throw new \Exception('ID da loja não informado');
            }
            
            $store = Store::find($storeId);
            if (!$store) {
                throw new \Exception("Loja com ID {$storeId} não encontrada");
            }

            $year = date('Y');
            $prefix = $isConserto ? 'CO' : 'OS';
            
            $counter = StoreCounter::where('store_id', $storeId)
                ->where('year', $year)
                ->first();

            if (!$counter) {
                // Se não existe contador, o próximo será 2012 para OS ou 2405 para CO
                $number = $isConserto ? 2405 : 2012;
            } else {
                if ($isConserto) {
                    // Se o contador de conserto estiver em 0 ou menor que 2404, próximo será 2405
                    $currentCo = $counter->current_co ?? 0;
                    if ($currentCo < 2404) {
                        $number = 2405;
                    } else {
                        $number = $currentCo + 1;
                    }
                } else {
                    // Se o contador estiver em 0 ou menor que 2011, próximo será 2012
                    $currentOs = $counter->current_os ?? 0;
                    if ($currentOs < 2011) {
                        $number = 2012;
                    } else {
                        $number = $currentOs + 1;
                    }
                }
            }

            $osNumber = $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            
            \Illuminate\Support\Facades\Log::info('✅ [OS] getNextOsNumber gerado', [
                'os_number' => $osNumber,
                'store_id' => $storeId,
                'is_conserto' => $isConserto,
                'year' => $year,
                'number' => $number
            ]);
            
            return $osNumber;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ [OS] Erro em getNextOsNumber', [
                'store_id' => $storeId,
                'is_conserto' => $isConserto,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public static function generateOsNumber($storeId, $isConserto = false)
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new \Exception('Loja não encontrada');
        }

        $year = date('Y');
        $prefix = $isConserto ? 'CO' : 'OS';
        
        DB::beginTransaction();
        try {
            $counter = StoreCounter::lockForUpdate()
                ->where('store_id', $storeId)
                ->where('year', $year)
                ->first();

            if (!$counter) {
                // Começar OS normais em 2012 e CO em 2405
                $counter = StoreCounter::create([
                    'store_id' => $storeId,
                    'year' => $year,
                    'current_os' => 2011, // Começar em 2011 para que o próximo seja 2012
                    'current_co' => 2404, // Começar em 2404 para que o próximo seja 2405
                ]);
            }

            if ($isConserto) {
                // Se o contador de conserto estiver em 0 ou menor que 2404, iniciar em 2405
                if ($counter->current_co < 2404) {
                    $counter->current_co = 2404;
                }
                $counter->current_co++;
                $counter->save();
                $number = $counter->current_co;
            } else {
                // Se o contador estiver em 0 ou menor que 2011, iniciar em 2012
                if ($counter->current_os < 2011) {
                    $counter->current_os = 2011;
                }
                $counter->current_os++;
                $counter->save();
                $number = $counter->current_os;
            }

            $osNumber = $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);

            DB::commit();
            return $osNumber;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function canEdit(): bool
    {
        return in_array($this->status, ['REGISTRADA', 'EM_PRODUCAO', 'PRONTA']);
    }

    public function canCancel(): bool
    {
        return $this->status !== 'ENTREGUE';
    }

    /**
     * Boot method para definir created_at usando data de trabalho
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($serviceOrder) {
            // Definir created_at usando a data de trabalho se disponível
            if (!$serviceOrder->created_at) {
                try {
                    $workDate = \App\Helpers\WorkDateHelper::getWorkDate();
                    $serviceOrder->created_at = $workDate;
                } catch (\Exception $e) {
                    // Se houver erro, deixar o Laravel definir automaticamente
                }
            }
        });
    }
}


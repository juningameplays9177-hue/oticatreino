<?php

namespace App\Console\Commands;

use App\Services\Finance\PayableRecurringService;
use Illuminate\Console\Command;

class ProcessRecurringPayables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:process-recurring-payables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processa contas a pagar recorrentes e gera próximas parcelas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Processando contas a pagar recorrentes...');
        
        try {
            $service = new PayableRecurringService();
            $count = $service->processRecurringPayables();
            
            if ($count > 0) {
                $this->info("✅ {$count} parcela(s) recorrente(s) gerada(s) com sucesso!");
            } else {
                $this->info('ℹ️  Nenhuma parcela recorrente precisa ser gerada no momento.');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Erro ao processar recorrências: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}


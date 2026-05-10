<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function cashflow(Request $request)
    {
        try {
            // Verificar se a tabela existe
            if (!Schema::hasTable('transactions')) {
                throw new \Exception('Tabela transactions não existe');
            }
            
            $from = $request->input('from', now()->startOfMonth());
            $to = $request->input('to', now()->endOfMonth());
            
            $user = auth()->user();
            $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);
            
            // Verificar se as tabelas relacionadas existem antes de fazer eager loading
            $with = [];
            if (Schema::hasTable('accounts')) {
                $with[] = 'drAccount';
                $with[] = 'crAccount';
            }
            if (Schema::hasTable('finance_categories')) $with[] = 'category';
            
            $transactions = Transaction::where('company_id', $companyId)
                ->whereBetween('txn_date', [$from, $to])
                ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
                ->when($request->account_id, function ($q) use ($request) {
                    $q->where(function ($query) use ($request) {
                        $query->where('dr_account_id', $request->account_id)
                              ->orWhere('cr_account_id', $request->account_id);
                    });
                })
                ->when(!empty($with), fn($q) => $q->with($with))
                ->orderBy('txn_date')
                ->get();

            return view('finance.reports.cashflow', compact('transactions', 'from', 'to'));
        } catch (\Throwable $e) {
            \Log::error('Erro ao carregar Cashflow: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('finance.reports.cashflow', [
                'transactions' => collect([]),
                'from' => $request->input('from', now()->startOfMonth()),
                'to' => $request->input('to', now()->endOfMonth()),
                'error' => 'Tabelas do módulo financeiro não foram criadas. Execute o SQL em database/finance_module.sql no phpMyAdmin. Erro: ' . $e->getMessage()
            ]);
        }
    }

    public function dre(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth());
        $to = $request->input('to', now()->endOfMonth());

        // Implementar cálculo de DRE
        // Simplificado - em produção, fazer agregações corretas
        
        return view('finance.reports.dre', compact('from', 'to'));
    }

    public function pdv(Request $request)
    {
        try {
            $from = $request->input('from', now()->startOfMonth());
            $to = $request->input('to', now()->endOfMonth());

            $user = auth()->user();
            $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);

            $sales = \App\Models\Sale::where('company_id', $companyId)
                ->whereBetween('sale_date', [$from, $to])
                ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
                ->with(['store', 'customer', 'payments'])
                ->orderBy('sale_date', 'desc')
                ->get();

            return view('finance.reports.pdv', compact('sales', 'from', 'to'));
        } catch (\Throwable $e) {
            \Log::error('Erro ao carregar Relatório PDV: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('finance.reports.pdv', [
                'sales' => collect([]),
                'from' => $request->input('from', now()->startOfMonth()),
                'to' => $request->input('to', now()->endOfMonth()),
                'error' => 'Erro ao carregar relatório: ' . $e->getMessage()
            ]);
        }
    }
}


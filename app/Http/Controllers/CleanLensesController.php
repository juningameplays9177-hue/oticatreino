<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CleanLensesController extends Controller
{
    public function clean()
    {
        // Buscar tipo de produto Lente
        $lensType = ProductType::where('code_prefix', 'L')->first();

        if (!$lensType) {
            return redirect()->back()->with('error', 'Tipo de produto "Lente" não encontrado!');
        }

        // Buscar todas as lentes
        $allLenses = Product::where('product_type_id', $lensType->id)->get();

        // Identificar lentes com código antigo (formato longo)
        // Código antigo: qualquer coisa que NÃO seja L001, L002, etc. (formato L seguido de exatamente 3 dígitos)
        $oldFormatLenses = $allLenses->filter(function ($lens) {
            // Retornar true se NÃO for formato sequencial (L001, L002, etc.)
            // Exemplos de código antigo: L-MULTIFOCALPROGR-6925afba2f0d1, L-KODAKPRECISE150-6925afba36815
            return !preg_match('/^L\d{3}$/', $lens->ref);
        });

        // Identificar lentes com código sequencial (L001, L002, etc.)
        $newFormatLenses = $allLenses->filter(function ($lens) {
            return preg_match('/^L\d{3}$/', $lens->ref);
        });

        $results = [
            'total_lenses' => $allLenses->count(),
            'old_format_count' => $oldFormatLenses->count(),
            'new_format_count' => $newFormatLenses->count(),
            'old_format_lenses' => $oldFormatLenses->map(function ($lens) {
                return [
                    'id' => $lens->id,
                    'ref' => $lens->ref,
                    'name' => $lens->name,
                ];
            })->values(),
        ];

        return view('clean-lenses', compact('results'));
    }

    public function confirm()
    {
        // Buscar tipo de produto Lente
        $lensType = ProductType::where('code_prefix', 'L')->first();

        if (!$lensType) {
            return redirect()->back()->with('error', 'Tipo de produto "Lente" não encontrado!');
        }

        // Buscar todas as lentes
        $allLenses = Product::where('product_type_id', $lensType->id)->get();

        // Identificar lentes com código antigo (formato longo)
        // Código antigo: qualquer coisa que NÃO seja L001, L002, etc. (formato L seguido de exatamente 3 dígitos)
        $oldFormatLenses = $allLenses->filter(function ($lens) {
            // Retornar true se NÃO for formato sequencial (L001, L002, etc.)
            // Exemplos de código antigo: L-MULTIFOCALPROGR-6925afba2f0d1, L-KODAKPRECISE150-6925afba36815
            return !preg_match('/^L\d{3}$/', $lens->ref);
        });

        if ($oldFormatLenses->isEmpty()) {
            return redirect()->route('clean.lenses')->with('success', 'Nenhuma lente duplicada encontrada!');
        }

        $deletedCount = 0;
        $deletedPrices = 0;

        // Usar DB transaction para garantir consistência
        DB::beginTransaction();
        try {
            // Coletar IDs das lentes antigas
            $oldLensIds = $oldFormatLenses->pluck('id')->toArray();
            
            if (!empty($oldLensIds)) {
                // Deletar preços associados
                $deletedPrices = ProductPrice::whereIn('product_id', $oldLensIds)->delete();
                
                // Deletar produtos
                $deletedCount = Product::whereIn('id', $oldLensIds)->delete();
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('clean.lenses')->with('error', 
                "Erro ao deletar lentes: " . $e->getMessage()
            );
        }

        return redirect()->route('clean.lenses')->with('success', 
            "Limpeza concluída! {$deletedCount} lentes e {$deletedPrices} preços removidos."
        );
    }
}


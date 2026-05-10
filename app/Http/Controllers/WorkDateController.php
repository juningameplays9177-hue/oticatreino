<?php

namespace App\Http\Controllers;

use App\Helpers\WorkDateHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkDateController extends Controller
{
    /**
     * Atualiza a data de trabalho na sessão
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'work_date' => ['required', 'date'],
        ]);

        // Validar se a data está dentro do mês atual e não é futura
        if (!WorkDateHelper::isValidWorkDate($request->work_date)) {
            throw ValidationException::withMessages([
                'work_date' => 'A data de trabalho deve estar entre o dia 1 do mês atual e a data atual.',
            ]);
        }

        // Salvar na sessão
        WorkDateHelper::setWorkDate($request->work_date);

        return redirect()->back()->with('success', 'Data de trabalho alterada com sucesso para ' . \Carbon\Carbon::parse($request->work_date)->format('d/m/Y') . '.');
    }
}

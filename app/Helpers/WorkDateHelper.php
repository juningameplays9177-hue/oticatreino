<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class WorkDateHelper
{
    /**
     * Obtém a data de trabalho da sessão ou retorna a data atual
     * 
     * @return Carbon
     */
    public static function getWorkDate(): Carbon
    {
        $workDate = Session::get('work_date');
        
        if ($workDate) {
            return Carbon::parse($workDate)->setTimezone('America/Sao_Paulo');
        }
        
        return Carbon::now('America/Sao_Paulo');
    }

    /**
     * Define a data de trabalho na sessão
     * 
     * @param string|Carbon $date
     * @return void
     */
    public static function setWorkDate($date): void
    {
        if ($date instanceof Carbon) {
            $date = $date->toDateString();
        }
        
        Session::put('work_date', $date);
    }

    /**
     * Valida se a data está dentro do mês atual e não é futura
     * 
     * @param string|Carbon $date
     * @return bool
     */
    public static function isValidWorkDate($date): bool
    {
        if ($date instanceof Carbon) {
            $carbonDate = $date;
        } else {
            $carbonDate = Carbon::parse($date)->setTimezone('America/Sao_Paulo');
        }
        
        $now = Carbon::now('America/Sao_Paulo');
        $firstDayOfMonth = $now->copy()->startOfMonth();
        
        // A data deve estar entre o dia 1 do mês atual e a data atual
        return $carbonDate->gte($firstDayOfMonth) && 
               $carbonDate->lte($now) &&
               $carbonDate->format('Y-m') === $now->format('Y-m');
    }

    /**
     * Obtém o primeiro dia disponível para seleção (dia 1 do mês atual)
     * 
     * @return Carbon
     */
    public static function getFirstAvailableDate(): Carbon
    {
        return Carbon::now('America/Sao_Paulo')->startOfMonth();
    }

    /**
     * Obtém a última data disponível para seleção (data atual)
     * 
     * @return Carbon
     */
    public static function getLastAvailableDate(): Carbon
    {
        return Carbon::now('America/Sao_Paulo');
    }

    /**
     * Limpa a data de trabalho da sessão
     * 
     * @return void
     */
    public static function clearWorkDate(): void
    {
        Session::forget('work_date');
    }
}

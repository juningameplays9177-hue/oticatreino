<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyLicense;
use Carbon\Carbon;

class LicenseService
{
    public static function checkAndUpdateLicenseStatus(CompanyLicense $license): void
    {
        $now = Carbon::now();

        // Verificar certificado
        if ($license->cert_valid_to && $now->gt($license->cert_valid_to)) {
            $license->cert_status = 'EXPIRADO';
        } elseif ($license->cert_valid_from && $license->cert_valid_to) {
            $license->cert_status = 'VALIDO';
        } else {
            $license->cert_status = 'NAO_CONFIGURADO';
        }

        // Verificar status da licença baseado na assinatura e certificado
        $subscription = $license->company->subscriptions()
            ->where('status', 'ATIVA')
            ->first();

        if (!$subscription) {
            $license->license_status = 'CANCELADA';
        } elseif ($license->cert_status === 'EXPIRADO') {
            $license->license_status = 'EXPIRADA';
        } elseif ($license->cert_status === 'VALIDO' && $subscription) {
            $license->license_status = 'ATIVA';
        } else {
            $license->license_status = 'PENDENTE';
        }

        $license->last_check_at = $now;
        $license->save();
    }

    public static function hasLicenseIssues(): bool
    {
        return CompanyLicense::whereIn('license_status', ['CANCELADA', 'EXPIRADA'])
            ->orWhere('cert_status', 'EXPIRADO')
            ->exists();
    }

    public static function getLicenseIssues(): array
    {
        $licenses = CompanyLicense::with('company')
            ->where(function ($query) {
                $query->whereIn('license_status', ['CANCELADA', 'EXPIRADA'])
                    ->orWhere('cert_status', 'EXPIRADO');
            })
            ->get();
        
        if ($licenses->isEmpty()) {
            return [];
        }
        
        return $licenses->map(function ($license) {
            $company = $license->company;
            return [
                'company' => $company ? ($company->trade_name ?? $company->legal_name ?? 'N/A') : 'N/A',
                'license_status' => $license->license_status,
                'cert_status' => $license->cert_status,
            ];
        })->toArray();
    }
}


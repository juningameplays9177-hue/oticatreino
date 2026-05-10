<?php

namespace App\Services;

use App\Models\ServiceOrder;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\View;

class PrintService
{
    public function generatePdf(ServiceOrder $serviceOrder)
    {
        $serviceOrder->load([
            'client',
            'store',
            'company',
            'employee',
            'items.product',
            'prescription.prescription',
        ]);

        $html = View::make('os.print', compact('serviceOrder'))->render();

        return PDF::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOption('enable-local-file-access', true);
    }
}


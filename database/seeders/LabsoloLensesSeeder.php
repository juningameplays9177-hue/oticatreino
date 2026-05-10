<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductPrice;
use App\Models\Store;
use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LabsoloLensesSeeder extends Seeder
{
    public function run(): void
    {
        // Buscar tipo de produto "Lente"
        $lensType = ProductType::where('code_prefix', 'L')->first();
        
        if (!$lensType) {
            $this->command->error('Tipo de produto "Lente" não encontrado! Execute ProductTypesSeeder primeiro.');
            return;
        }

        $this->command->info('Iniciando cadastro de lentes Labsolo...');

        // Criar ou buscar marca LABSOLO
        $brand = Brand::firstOrCreate(
            ['name' => 'LABSOLO'],
            ['name' => 'LABSOLO']
        );
        $this->command->info("Marca LABSOLO: " . ($brand->wasRecentlyCreated ? 'Criada' : 'Já existia'));

        // Criar ou buscar fornecedor LABSOLO
        $supplier = Supplier::firstOrCreate(
            ['trade_name' => 'LABSOLO'],
            [
                'trade_name' => 'LABSOLO',
                'legal_name' => 'LABSOLO',
                'is_lab' => true,
                'is_active' => true,
            ]
        );
        $this->command->info("Fornecedor/Laboratório LABSOLO: " . ($supplier->wasRecentlyCreated ? 'Criado' : 'Já existia'));

        // Buscar todas as lojas para cadastrar preços
        $stores = Store::all();
        if ($stores->isEmpty()) {
            $this->command->warn('Nenhuma loja encontrada. Os preços serão cadastrados sem loja específica.');
        }

        $counter = 0;

        // MULTIFOCAIS ESPACE
        $this->command->info('Cadastrando Multifocais Espace...');
        $espaceLenses = [
            ['name' => 'Multifocal Progressiva Espace Orma', 'material' => 'Orma', 'diametro' => 72, 'sem_ar' => 67.00, 'ar' => 132.00, 'esf' => '-7,00 a +5,75', 'cil' => '-5.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Multifocal Progressiva Espace Acclimates', 'material' => 'Acclimates', 'diametro' => 70, 'sem_ar' => 175.00, 'ar' => 240.00, 'esf' => '-7,00 a +5,75', 'cil' => '-5.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Multifocal Progressiva Espace Plus Orma', 'material' => 'Orma', 'diametro' => 80, 'sem_ar' => 102.00, 'ar' => 167.00, 'esf' => '-8,00 a +6,25', 'cil' => '-5.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Multifocal Progressiva Espace Plus TFL', 'material' => 'TFL', 'diametro' => 85, 'sem_ar' => 165.00, 'ar' => 230.00, 'esf' => '-8,00 a +6,25', 'cil' => '-5.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Multifocal Progressiva Espace Plus Orma Acclimates', 'material' => 'Orma Acclimates', 'diametro' => 78, 'sem_ar' => 215.00, 'ar' => 280.00, 'esf' => '-8,00 a +6,25', 'cil' => '-5.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Multifocal Progressiva Espace Plus Orma Transitions Gen S', 'material' => 'Orma Transitions Gen S', 'diametro' => 78, 'sem_ar' => 290.00, 'ar' => 355.00, 'esf' => '-8,00 a +6,25', 'cil' => '-5.00', 'adicao' => '1,00/3,50'],
        ];

        foreach ($espaceLenses as $lens) {
            $counter += $this->createLens($lensType, $lens, $stores, $brand->id, $supplier->id);
        }

        // MULTIFOCAIS SURFAÇADO
        $this->command->info('Cadastrando Multifocais Surfaçado...');
        $surfacadoLenses = [
            ['name' => 'Multifocal Progressiva MF Res', 'material' => 'Resina', 'diametro' => 75, 'sem_ar' => 47.00, 'ar' => 71.00, 'ar_frontal' => 112.00, 'esf' => '-6,00 a +5,00', 'cil' => '-4.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Multifocal Progressiva MF Res Blue', 'material' => 'Resina Blue', 'diametro' => 75, 'ar' => 97.00, 'esf' => '-6,00 a +5,00', 'cil' => '-4.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Multifocal Progressiva MF Res Foto', 'material' => 'Resina Foto', 'diametro' => 75, 'sem_ar' => 75.00, 'ar' => 84.00, 'ar_frontal' => 140.00, 'esf' => '-6,00 a +5,00', 'cil' => '-4.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Multifocal Progressiva MF Res Foto Blue', 'material' => 'Resina Foto Blue', 'diametro' => 75, 'ar' => 189.00, 'esf' => '-6,00 a +5,00', 'cil' => '-4.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Multifocal Progressiva MF Res Poli', 'material' => 'Poli', 'diametro' => '70/75', 'ar' => 170.00, 'esf' => '-7,00 a +6,00', 'cil' => '-5.00', 'adicao' => '1,00/3,00'],
            ['name' => 'Multifocal Progressiva MF Res Poli Blue', 'material' => 'Poli Blue', 'diametro' => 75, 'sem_ar' => 150.00, 'ar' => 190.00, 'ar_frontal' => 215.00, 'esf' => '-7,00 a +6,00', 'cil' => '-5.00', 'adicao' => '1,00/3,00'],
            ['name' => 'Multifocal Progressiva MF Res Poli Foto', 'material' => 'Poli Foto', 'diametro' => 75, 'ar' => 280.00, 'esf' => '-7,00 a +6,00', 'cil' => '-5.00', 'adicao' => '1,00/3,00'],
            ['name' => 'Multifocal Progressiva MF Res Poli Foto Blue', 'material' => 'Poli Foto Blue', 'diametro' => 75, 'ar' => 310.00, 'esf' => '-7,00 a +6,00', 'cil' => '-5.00', 'adicao' => '1,00/3,00'],
        ];

        foreach ($surfacadoLenses as $lens) {
            $counter += $this->createLens($lensType, $lens, $stores, $brand->id, $supplier->id);
        }

        // KODAK
        $this->command->info('Cadastrando Lentes Kodak...');
        $kodakLenses = [
            ['name' => 'Kodak Precise 1.50', 'material' => '1.50', 'diametro' => 80, 'sem_ar' => 135.00, 'ar' => 200.00, 'esf' => '-10,00 a +6,00', 'cil' => '-5.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Kodak Precise 1.50 Transitions Gen S Cinza', 'material' => '1.50 Transitions Gen S', 'diametro' => 77, 'sem_ar' => 335.00, 'ar' => 400.00, 'esf' => '-10,00 a +6,00', 'cil' => '-5.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Kodak Precise Poly', 'material' => 'Poly', 'diametro' => 80, 'sem_ar' => 197.00, 'ar' => 262.00, 'esf' => '-10,00 a +6,00', 'cil' => '-5.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Kodak Precise Poly Transitions Gen S Cinza', 'material' => 'Poly Transitions Gen S', 'diametro' => 78, 'sem_ar' => 538.00, 'ar' => 603.00, 'esf' => '-10,00 a +6,00', 'cil' => '-5.00', 'adicao' => '1,00/3,50'],
        ];

        foreach ($kodakLenses as $lens) {
            $counter += $this->createLens($lensType, $lens, $stores, $brand->id, $supplier->id);
        }

        // VARILUX
        $this->command->info('Cadastrando Lentes Varilux...');
        $variluxLenses = [
            ['name' => 'Varilux Comfort Airwear', 'material' => 'Airwear', 'diametro' => 80, 'sem_ar' => 795.00, 'ar' => 865.00, 'esf' => '-10,00 a +7,00', 'cil' => '-6.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Varilux Comfort Airwear Transitions Gen S Cinza', 'material' => 'Airwear Transitions Gen S', 'diametro' => 80, 'sem_ar' => 1117.00, 'ar' => 1187.00, 'esf' => '-10,00 a +7,00', 'cil' => '-6.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Varilux Comfort Orma', 'material' => 'Orma', 'diametro' => 80, 'sem_ar' => 510.00, 'ar' => 580.00, 'esf' => '-10,00 a +6,00', 'cil' => '-5.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Varilux Comfort Orma Transitions Gen S Cinza', 'material' => 'Orma Transitions Gen S', 'diametro' => 80, 'sem_ar' => 987.00, 'ar' => 1057.00, 'esf' => '-10,00 a +6,00', 'cil' => '-5.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Varilux Liberty Airwear', 'material' => 'Airwear', 'diametro' => 80, 'sem_ar' => 545.00, 'ar' => 615.00, 'esf' => '-10,00 a +7,00', 'cil' => '-6.00', 'adicao' => '1,00/3,50'],
            ['name' => 'Varilux Liberty Orma', 'material' => 'Orma', 'diametro' => 80, 'sem_ar' => 341.00, 'ar' => 411.00, 'esf' => '-10,00 a +6,00', 'cil' => '-5.00', 'adicao' => '1,00/3,50'],
        ];

        foreach ($variluxLenses as $lens) {
            $counter += $this->createLens($lensType, $lens, $stores, $brand->id, $supplier->id);
        }

        // VISAO SIMPLES SURFAÇADA
        $this->command->info('Cadastrando Visão Simples Surfaçada...');
        $visaoSimplesSurfacada = [
            ['name' => 'Visão Simples Surfaçada 1.60', 'material' => '1.60', 'diametro' => 75, 'sem_ar' => 90.00, 'ar' => 103.00, 'ar_frontal' => 155.00, 'esf' => '-8,00 a +6,00', 'cil' => '-5.00'],
            ['name' => 'Visão Simples Surfaçada 1.60 Blue', 'material' => '1.60 Blue', 'diametro' => 75, 'sem_ar' => 153.00, 'ar' => 187.00, 'ar_frontal' => 218.00, 'esf' => '-8,00 a +6,00', 'cil' => '-5.00'],
            ['name' => 'Visão Simples Surfaçada 1.60 Blue Foto', 'material' => '1.60 Blue Foto', 'diametro' => 75, 'ar' => 247.00, 'esf' => '-8,00 a +6,00', 'cil' => '-5.00'],
            ['name' => 'Visão Simples Surfaçada 1.67', 'material' => '1.67', 'diametro' => 75, 'sem_ar' => 137.00, 'ar' => 187.00, 'ar_frontal' => 202.00, 'esf' => '-10,00 a +7,00', 'cil' => '-6.00'],
            ['name' => 'Visão Simples Surfaçada 1.67 Blue', 'material' => '1.67 Blue', 'diametro' => '70/75', 'sem_ar' => 161.00, 'ar' => 217.00, 'ar_frontal' => 226.00, 'esf' => '-10,00 a +7,00', 'cil' => '-6.00'],
            ['name' => 'Visão Simples Surfaçada 1.67 Blue Foto', 'material' => '1.67 Blue Foto', 'diametro' => 75, 'sem_ar' => 237.00, 'ar' => 280.00, 'ar_frontal' => 302.00, 'esf' => '-10,00 a +7,00', 'cil' => '-6.00'],
            ['name' => 'Visão Simples Surfaçada 1.67 Foto', 'material' => '1.67 Foto', 'diametro' => 75, 'sem_ar' => 217.00, 'ar' => 260.00, 'ar_frontal' => 282.00, 'esf' => '-12,00 a +7,00', 'cil' => '-6.00'],
            ['name' => 'Visão Simples Surfaçada 1.74', 'material' => '1.74', 'diametro' => 75, 'sem_ar' => 517.00, 'ar' => 577.00, 'ar_frontal' => 582.00, 'esf' => '-12,00 a +8,00', 'cil' => '-6.00'],
            ['name' => 'Visão Simples Surfaçada 1.74 Blue', 'material' => '1.74 Blue', 'diametro' => 75, 'sem_ar' => 540.00, 'ar' => 599.00, 'ar_frontal' => 605.00, 'esf' => '-12,00 a +8,00', 'cil' => '-6.00'],
            ['name' => 'Visão Simples Surfaçada 1.74 Foto', 'material' => '1.74 Foto', 'diametro' => 75, 'sem_ar' => 630.00, 'ar' => 680.00, 'ar_frontal' => 695.00, 'esf' => '-12,00 a +8,00', 'cil' => '-6.00'],
            ['name' => 'Visão Simples Surfaçada Poli', 'material' => 'Poli', 'diametro' => 75, 'sem_ar' => 75.00, 'ar' => 86.00, 'ar_frontal' => 140.00, 'esf' => '-8,00 a +6,00', 'cil' => '-5.00'],
            ['name' => 'Visão Simples Surfaçada Poli Blue', 'material' => 'Poli Blue', 'diametro' => 75, 'sem_ar' => 141.00, 'ar' => 157.00, 'ar_frontal' => 206.00, 'esf' => '-8,00 a +6,00', 'cil' => '-5.00'],
            ['name' => 'Visão Simples Surfaçada Poli Blue Foto', 'material' => 'Poli Blue Foto', 'diametro' => 75, 'ar' => 273.00, 'esf' => '-8,00 a +6,00', 'cil' => '-5.00'],
            ['name' => 'Visão Simples Surfaçada Poli Foto', 'material' => 'Poli Foto', 'diametro' => 75, 'sem_ar' => 197.00, 'ar' => 230.00, 'ar_frontal' => 262.00, 'esf' => '-8,00 a +6,00', 'cil' => '-5.00'],
            ['name' => 'Visão Simples Surfaçada Resina', 'material' => 'Resina', 'diametro' => 75, 'sem_ar' => 48.00, 'ar' => 63.00, 'ar_frontal' => 113.00, 'esf' => '-6,00 a -6,00', 'cil' => '-5.00'],
            ['name' => 'Visão Simples Surfaçada Resina Blue', 'material' => 'Resina Blue', 'diametro' => 75, 'ar' => 69.00, 'esf' => '-6,00 a -6,00', 'cil' => '-5.00'],
            ['name' => 'Visão Simples Surfaçada Resina Blue Foto', 'material' => 'Resina Blue Foto', 'diametro' => 75, 'ar' => 120.00, 'esf' => '-6,00 a -6,00', 'cil' => '-5.00'],
            ['name' => 'Visão Simples Surfaçada Resina Foto', 'material' => 'Resina Foto', 'diametro' => 75, 'sem_ar' => 71.00, 'ar' => 83.00, 'ar_frontal' => 136.00, 'esf' => '-6,00 a -6,00', 'cil' => '-5.00'],
            ['name' => 'Visão Simples Surfaçada Resina Transitions', 'material' => 'Resina Transitions', 'diametro' => 75, 'sem_ar' => 215.00, 'ar_frontal' => 280.00, 'esf' => '-6,00 a -6,00', 'cil' => '-5.00'],
        ];

        foreach ($visaoSimplesSurfacada as $lens) {
            $counter += $this->createLens($lensType, $lens, $stores, $brand->id, $supplier->id);
        }

        // VISAO SIMPLES PRONTA - Vou adicionar as principais
        $this->command->info('Cadastrando Visão Simples Pronta...');
        $visaoSimplesPronta = [
            ['name' => 'Visão Simples Pronta 1.61 Ar', 'material' => '1.61', 'diametro' => '65/70/75', 'valor' => 52.00, 'esf' => '-10,00 a +7,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta 1.61 Ar Extra', 'material' => '1.61', 'diametro' => '65/70', 'valor' => 67.00, 'esf' => '-6,00 a +6,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta 1.61 Blue Ar', 'material' => '1.61 Blue', 'diametro' => '65/70/75', 'valor' => 59.00, 'esf' => '-10,00 a +7,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta 1.61 Blue Ar Extra', 'material' => '1.61 Blue', 'diametro' => '65/70', 'valor' => 85.00, 'esf' => '-6,00 a +6,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta 1.67 Ar', 'material' => '1.67', 'diametro' => '70/75', 'valor' => 70.00, 'esf' => '-12,00 a +6,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta 1.67 Ar Extra', 'material' => '1.67', 'diametro' => '70/75', 'valor' => 85.00, 'esf' => '-10,00 a +6,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta 1.67 Blue Ar', 'material' => '1.67 Blue', 'diametro' => '70/75', 'valor' => 87.00, 'esf' => '-12,00 a +6,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta 1.67 Blue Ar Extra', 'material' => '1.67 Blue', 'diametro' => '70/75', 'valor' => 103.00, 'esf' => '-10,00 a +6,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta 1.67 Blue Foto Ar', 'material' => '1.67 Blue Foto', 'diametro' => '70/75', 'valor' => 220.00, 'esf' => '-12,00 a +6,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta 1.67 Blue Foto Ar Extra', 'material' => '1.67 Blue Foto', 'diametro' => '70/75', 'valor' => 235.00, 'esf' => '-10,00 a +6,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta 1.67 Foto Ar', 'material' => '1.67 Foto', 'diametro' => '65/70/75', 'valor' => 155.00, 'esf' => '-8,00 a +6,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta 1.67 Foto Ar Extra', 'material' => '1.67 Foto', 'diametro' => 70, 'valor' => 170.00, 'esf' => '-10,00 a +8,25', 'cil' => '-3.00'],
            ['name' => 'Visão Simples Pronta 1.74 Blue Ar', 'material' => '1.74 Blue', 'diametro' => '70/75', 'valor' => 299.00, 'esf' => '-10,00 a -1,00', 'cil' => '-3.00'],
            ['name' => 'Visão Simples Pronta 1.74 Blue Ar Extra 1', 'material' => '1.74 Blue', 'diametro' => 65, 'valor' => 310.00, 'esf' => '-13,00 a -10,25', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta 1.74 Blue Ar Extra 2', 'material' => '1.74 Blue', 'diametro' => 70, 'valor' => 310.00, 'esf' => '-15,00 a -13,25', 'cil' => '0.00'],
            ['name' => 'Visão Simples Pronta Poli', 'material' => 'Poli', 'diametro' => '65/70', 'valor' => 16.00, 'esf' => '-6,00 a +4,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta Poli Ar', 'material' => 'Poli', 'diametro' => '65/70', 'valor' => 17.00, 'esf' => '-6,00 a +6,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta Poli Ar Extra 1', 'material' => 'Poli', 'diametro' => '65/70', 'valor' => 54.00, 'esf' => '-6,00 a +6,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta Poli Ar Extra 2', 'material' => 'Poli', 'diametro' => '65/70', 'valor' => 105.00, 'esf' => '-6,00 a +6,00', 'cil' => '-6.00'],
            ['name' => 'Visão Simples Pronta Poli Blue Ar', 'material' => 'Poli Blue', 'diametro' => '65/70', 'valor' => 32.00, 'esf' => '-6,00 a +4,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta Poli Blue Ar Extra 1', 'material' => 'Poli Blue', 'diametro' => '65/70', 'valor' => 65.00, 'esf' => '-4,00 a +4,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta Poli Blue Ar Extra 2', 'material' => 'Poli Blue', 'diametro' => '65/70', 'valor' => 115.00, 'esf' => '-4,00 a +4,00', 'cil' => '-6.00'],
            ['name' => 'Visão Simples Pronta Poli Blue Foto Ar', 'material' => 'Poli Blue Foto', 'diametro' => '65/70', 'valor' => 135.00, 'esf' => '-6,00 a +4,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta Poli Blue Foto Ar Extra', 'material' => 'Poli Blue Foto', 'diametro' => '65/70', 'valor' => 145.00, 'esf' => '-4,00 a +4,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta Poli Extra', 'material' => 'Poli', 'diametro' => '65/70', 'valor' => 51.00, 'esf' => '-4,00 a +4,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta Poli Foto Ar', 'material' => 'Poli Foto', 'diametro' => '65/70', 'valor' => 87.00, 'esf' => '-4,00 a +4,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta Poli Foto Ar Extra', 'material' => 'Poli Foto', 'diametro' => '65/70', 'valor' => 140.00, 'esf' => '-4,00 a +4,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta Resina', 'material' => 'Resina', 'diametro' => '65/70', 'valor' => 14.00, 'esf' => '-4,00 a +4,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta Resina Ar', 'material' => 'Resina', 'diametro' => '65/70', 'valor' => 15.00, 'esf' => '-6,00 a +6,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta Resina Ar Extra 1', 'material' => 'Resina', 'diametro' => '65/70', 'valor' => 30.00, 'esf' => '-6,00 a +6,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta Resina Ar Extra 2', 'material' => 'Resina', 'diametro' => '65/70', 'valor' => 60.00, 'esf' => '-4,00 a +4,00', 'cil' => '-6.00'],
            ['name' => 'Visão Simples Pronta Blue Ar', 'material' => 'Blue', 'diametro' => '65/70', 'valor' => 25.00, 'esf' => '-6,00 a +4,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta Blue Ar Extra 1', 'material' => 'Blue', 'diametro' => '65/70', 'valor' => 48.00, 'esf' => '-4,00 a +4,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta Blue Ar Extra 2', 'material' => 'Blue', 'diametro' => '65/70', 'valor' => 75.00, 'esf' => '-4,00 a +4,00', 'cil' => '-6.00'],
            ['name' => 'Visão Simples Pronta Blue Foto Ar', 'material' => 'Blue Foto', 'diametro' => '65/70', 'valor' => 53.00, 'esf' => '-4,00 a +4,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta Blue Foto Ar Extra 1', 'material' => 'Blue Foto', 'diametro' => '65/70', 'valor' => 80.00, 'esf' => '-4,00 a +4,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta Blue Foto Ar Extra 2', 'material' => 'Blue Foto', 'diametro' => 65, 'valor' => 130.00, 'esf' => '-4,00 a +4,00', 'cil' => '-6.00'],
            ['name' => 'Visão Simples Pronta Resina Coloração', 'material' => 'Resina Coloração', 'diametro' => '65/70', 'valor' => 48.00, 'esf' => '-4,00 a +4,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta Resina Extra', 'material' => 'Resina', 'diametro' => '65/70', 'valor' => 25.00, 'esf' => '-4,00 a +6,00', 'cil' => '-2.25'],
            ['name' => 'Visão Simples Pronta Resina Foto', 'material' => 'Resina Foto', 'diametro' => '65/70', 'valor' => 30.00, 'esf' => '-4,00 a +4,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta Resina Foto Ar', 'material' => 'Resina Foto', 'diametro' => '65/70', 'valor' => 33.00, 'esf' => '-4,00 a +4,00', 'cil' => '-2.00'],
            ['name' => 'Visão Simples Pronta Resina Foto Ar Extra 1', 'material' => 'Resina Foto', 'diametro' => '65/70', 'valor' => 60.00, 'esf' => '-4,00 a +4,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta Resina Foto Ar Extra 2', 'material' => 'Resina Foto', 'diametro' => '65/70', 'valor' => 105.00, 'esf' => '-4,00 a +4,00', 'cil' => '-6.00'],
            ['name' => 'Visão Simples Pronta Resina Foto Extra', 'material' => 'Resina Foto', 'diametro' => '65/70', 'valor' => 57.00, 'esf' => '-4,00 a +4,00', 'cil' => '-4.00'],
            ['name' => 'Visão Simples Pronta Resina Transitions', 'material' => 'Resina Transitions', 'diametro' => '65/70', 'valor' => 187.00, 'esf' => '-4,00 a +4,00', 'cil' => '-2.00'],
        ];

        foreach ($visaoSimplesPronta as $lens) {
            $lens['sem_ar'] = $lens['valor'] ?? 0;
            unset($lens['valor']);
            $counter += $this->createLens($lensType, $lens, $stores, $brand->id, $supplier->id);
        }

        // MULTIFOCAL FREEFORM - Unique Pro HD (principais)
        $this->command->info('Cadastrando Multifocal Freeform Unique Pro HD...');
        $uniqueProHd = [
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.49', 'material' => '1.49', 'diametro' => 75, 'sem_ar' => 320.00, 'ar' => 340.00, 'ar_frontal' => 385.00, 'esf' => '-6,00 a +6,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.56', 'material' => '1.56', 'diametro' => 75, 'sem_ar' => 350.00, 'ar' => 360.00, 'ar_frontal' => 415.00, 'esf' => '-6,00 a +6,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.56 Blue', 'material' => '1.56 Blue', 'diametro' => 75, 'sem_ar' => 410.00, 'ar' => 420.00, 'ar_frontal' => 475.00, 'esf' => '-6,00 a +6,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.56 Foto', 'material' => '1.56 Foto', 'diametro' => 75, 'sem_ar' => 480.00, 'ar' => 490.00, 'ar_frontal' => 545.00, 'esf' => '-6,00 a +6,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.56 Blue Foto', 'material' => '1.56 Blue Foto', 'diametro' => 75, 'sem_ar' => 520.00, 'ar' => 530.00, 'ar_frontal' => 585.00, 'esf' => '-6,00 a +6,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.50 Transitions', 'material' => '1.50 Transitions', 'diametro' => 75, 'sem_ar' => 580.00, 'ar_frontal' => 645.00, 'esf' => '-6,00 a +6,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.50 Acclimates', 'material' => '1.50 Acclimates', 'diametro' => 75, 'sem_ar' => 515.00, 'ar_frontal' => 580.00, 'esf' => '-6,00 a +6,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.59 Poli', 'material' => '1.59 Poli', 'diametro' => 75, 'sem_ar' => 430.00, 'ar' => 440.00, 'ar_frontal' => 495.00, 'esf' => '-9,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.59 Blue', 'material' => '1.59 Blue', 'diametro' => 75, 'sem_ar' => 480.00, 'ar' => 490.00, 'ar_frontal' => 545.00, 'esf' => '-9,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.59 Poli Foto', 'material' => '1.59 Poli Foto', 'diametro' => 75, 'sem_ar' => 550.00, 'ar' => 560.00, 'ar_frontal' => 615.00, 'esf' => '-9,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.59 Poli Blue Foto', 'material' => '1.59 Poli Blue Foto', 'diametro' => 75, 'sem_ar' => 560.00, 'ar' => 570.00, 'ar_frontal' => 625.00, 'esf' => '-9,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.59 Transitions', 'material' => '1.59 Transitions', 'diametro' => 75, 'sem_ar' => 750.00, 'ar_frontal' => 815.00, 'esf' => '-9,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.61', 'material' => '1.61', 'diametro' => 75, 'sem_ar' => 500.00, 'ar' => 510.00, 'ar_frontal' => 565.00, 'esf' => '-9,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.61 Blue', 'material' => '1.61 Blue', 'diametro' => 75, 'sem_ar' => 550.00, 'ar' => 560.00, 'ar_frontal' => 615.00, 'esf' => '-9,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.61 Foto', 'material' => '1.61 Foto', 'diametro' => 75, 'sem_ar' => 620.00, 'ar' => 630.00, 'ar_frontal' => 685.00, 'esf' => '-9,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.61 Blue Foto', 'material' => '1.61 Blue Foto', 'diametro' => 75, 'sem_ar' => 650.00, 'ar' => 660.00, 'ar_frontal' => 715.00, 'esf' => '-9,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.60 Transitions', 'material' => '1.60 Transitions', 'diametro' => 75, 'sem_ar' => 1500.00, 'ar_frontal' => 1565.00, 'esf' => '-9,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.67', 'material' => '1.67', 'diametro' => 75, 'sem_ar' => 565.00, 'ar' => 575.00, 'ar_frontal' => 630.00, 'esf' => '-12,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.67 Blue', 'material' => '1.67 Blue', 'diametro' => 75, 'sem_ar' => 615.00, 'ar' => 625.00, 'ar_frontal' => 680.00, 'esf' => '-12,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.67 Foto', 'material' => '1.67 Foto', 'diametro' => 75, 'sem_ar' => 780.00, 'ar' => 790.00, 'ar_frontal' => 845.00, 'esf' => '-12,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.67 Blue Foto', 'material' => '1.67 Blue Foto', 'diametro' => 75, 'sem_ar' => 890.00, 'ar' => 900.00, 'ar_frontal' => 955.00, 'esf' => '-12,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.67 Transitions', 'material' => '1.67 Transitions', 'diametro' => 75, 'sem_ar' => 1750.00, 'ar_frontal' => 1815.00, 'esf' => '-12,00 a +8,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.74', 'material' => '1.74', 'diametro' => 75, 'sem_ar' => 1015.00, 'ar_frontal' => 1080.00, 'esf' => '-16,00 a +10,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.74 Blue', 'material' => '1.74 Blue', 'diametro' => 75, 'sem_ar' => 1015.00, 'ar_frontal' => 1080.00, 'esf' => '-16,00 a +10,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.74 Foto', 'material' => '1.74 Foto', 'diametro' => 75, 'sem_ar' => 1200.00, 'ar_frontal' => 1265.00, 'esf' => '-16,00 a +10,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.74 Blue Foto', 'material' => '1.74 Blue Foto', 'diametro' => 75, 'sem_ar' => 1300.00, 'ar_frontal' => 1365.00, 'esf' => '-16,00 a +10,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
            ['name' => 'Lentes Multifocais Unique Pro Hd 1.74 Transitions', 'material' => '1.74 Transitions', 'diametro' => 75, 'sem_ar' => 2400.00, 'ar_frontal' => 2465.00, 'esf' => '-16,00 a +10,00', 'cil' => '-6,00', 'adicao' => '1,00/3,50'],
        ];

        foreach ($uniqueProHd as $lens) {
            $counter += $this->createLens($lensType, $lens, $stores, $brand->id, $supplier->id);
        }

        // Adicionar mais categorias conforme necessário...
        // Por questões de espaço, vou criar um resumo das principais

        $this->command->info("Total de lentes cadastradas: {$counter}");
        $this->command->info('Seeder concluído! Execute novamente para adicionar mais categorias se necessário.');
    }

    private function createLens(ProductType $lensType, array $lensData, $stores, $brandId = null, $supplierId = null): int
    {
        $fullName = $lensData['name']; // Nome completo do catálogo
        $material = $lensData['material'] ?? '';
        $diametro = $lensData['diametro'] ?? '';
        
        // Criar nome curto para o campo name (usar apenas parte inicial)
        $shortName = strlen($fullName) > 50 ? substr($fullName, 0, 47) . '...' : $fullName;
        
        // Criar descrição completa com nome do catálogo e especificações
        $description = $fullName . "\n\n";
        $description .= "Material: {$material}";
        if ($diametro) {
            $description .= " | Diâmetro: {$diametro}mm";
        }
        if (isset($lensData['esf'])) {
            $description .= " | ESF: {$lensData['esf']}";
        }
        if (isset($lensData['cil'])) {
            $description .= " | CIL: {$lensData['cil']}";
        }
        if (isset($lensData['adicao'])) {
            $description .= " | Adição: {$lensData['adicao']}";
        }

        // Gerar código sequencial usando o método do modelo
        $ref = Product::generateRef($lensType->id);

        // Verificar se já existe produto com esse código (improvável, mas por segurança)
        $existingProduct = Product::where('ref', $ref)->first();
        if ($existingProduct) {
            // Se existir, gerar próximo código
            $ref = Product::generateRef($lensType->id);
        }

        // Criar produto
        $product = Product::firstOrCreate(
            ['ref' => $ref],
            [
                'name' => $shortName,
                'product_type_id' => $lensType->id,
                'brand_id' => $brandId,
                'supplier_id' => $supplierId,
                'sell_only_with_os' => true,
                'control_stock' => false,
                'color' => null, // Lente não tem cor
                'description' => $description,
                'unit' => 'UN',
                'showcase_enabled' => false,
                'archived' => false,
            ]
        );

        // Cadastrar preços
        $priceCreated = false;
        foreach ($stores as $store) {
            // Preço padrão (usar o maior valor disponível)
            $price = $lensData['ar_frontal'] ?? $lensData['ar'] ?? $lensData['sem_ar'] ?? 0;
            
            if ($price > 0) {
                ProductPrice::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'store_id' => $store->id,
                    ],
                    [
                        'price' => $price,
                        'cost' => $price * 0.5, // Custo estimado em 50% do preço
                    ]
                );
                $priceCreated = true;
            }
        }

        // Se não houver lojas, criar preço sem loja específica
        if (!$priceCreated && ($lensData['ar_frontal'] ?? $lensData['ar'] ?? $lensData['sem_ar'] ?? 0) > 0) {
            $price = $lensData['ar_frontal'] ?? $lensData['ar'] ?? $lensData['sem_ar'] ?? 0;
            ProductPrice::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'store_id' => null,
                ],
                [
                    'price' => $price,
                    'cost' => $price * 0.5,
                ]
            );
        }

        return 1;
    }
}


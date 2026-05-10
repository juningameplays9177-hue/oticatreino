<?php

namespace Database\Seeders;

use App\Models\ProductType;
use Illuminate\Database\Seeder;

class ProductTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Produto', 'code_prefix' => 'P'],
            ['name' => 'Lente', 'code_prefix' => 'L'],
            ['name' => 'Conserto', 'code_prefix' => 'C'],
        ];

        foreach ($types as $type) {
            ProductType::firstOrCreate(
                ['code_prefix' => $type['code_prefix']],
                [
                    'name' => $type['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}


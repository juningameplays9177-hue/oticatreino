<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\ProductGroup;
use App\Models\ProductSubgroup;
use App\Models\Supplier;
use App\Models\Store;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use Carbon\Carbon;

class ProductsImportController extends Controller
{
    public function show()
    {
        return view('products.import');
    }

    public function run(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx|max:5120',
        ]);

        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                return redirect()->back()->with('error', 'O arquivo está vazio.');
            }

            $header = array_map('strtolower', array_map('trim', array_shift($rows)));

            if (count($rows) > 1000) {
                return redirect()->back()->with('error', 'O arquivo possui mais de 1.000 linhas. O limite é 1.000.');
            }

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // +2 porque começa na linha 2 (após header)

                try {
                    $data = [];
                    foreach ($header as $colIndex => $headerName) {
                        $data[$headerName] = $row[$colIndex] ?? null;
                    }

                    // Normalizar dados
                    $name = trim($data['name'] ?? '');
                    if (empty($name)) {
                        $skipped++;
                        $errors[] = "Linha {$rowNum}: Nome não pode estar vazio.";
                        continue;
                    }

                    $ref = !empty($data['ref']) ? trim($data['ref']) : null;
                    $ean13 = !empty($data['ean13']) ? preg_replace('/[^0-9]/', '', $data['ean13']) : null;
                    if ($ean13 && strlen($ean13) !== 13) {
                        $ean13 = null; // Invalidar EAN se não tiver 13 dígitos
                    }

                    // Buscar ou criar grupos/grifes/fornecedores
                    $groupId = null;
                    if (!empty($data['group'])) {
                        $group = ProductGroup::firstOrCreate(
                            ['name' => trim($data['group'])],
                            ['name' => trim($data['group'])]
                        );
                        $groupId = $group->id;
                    }

                    $subgroupId = null;
                    if (!empty($data['subgroup']) && $groupId) {
                        $subgroup = ProductSubgroup::firstOrCreate(
                            [
                                'group_id' => $groupId,
                                'name' => trim($data['subgroup']),
                            ],
                            [
                                'group_id' => $groupId,
                                'name' => trim($data['subgroup']),
                            ]
                        );
                        $subgroupId = $subgroup->id;
                    }

                    $brandId = null;
                    if (!empty($data['brand'])) {
                        $brand = Brand::firstOrCreate(
                            ['name' => trim($data['brand'])],
                            ['name' => trim($data['brand'])]
                        );
                        $brandId = $brand->id;
                    }

                    $supplierId = null;
                    if (!empty($data['supplier'])) {
                        $supplierName = trim($data['supplier']);
                        $supplier = Supplier::firstOrCreate(
                            ['legal_name' => $supplierName],
                            [
                                'legal_name' => $supplierName,
                                'trade_name' => $supplierName,
                            ]
                        );
                        $supplierId = $supplier->id;
                    }

                    // Upsert produto
                    $productData = [
                        'name' => $name,
                        'ref' => $ref,
                        'ean13' => $ean13,
                        'unit' => strtoupper($data['unit'] ?? 'UN'),
                        'group_id' => $groupId,
                        'subgroup_id' => $subgroupId,
                        'brand_id' => $brandId,
                        'supplier_id' => $supplierId,
                        'color' => $data['color'] ?? null,
                        'size' => $data['size'] ?? null,
                        'shape' => $data['shape'] ?? null,
                        'sell_only_with_os' => !empty($data['sell_only_with_os']),
                        'control_stock' => !empty($data['control_stock']),
                        'showcase_enabled' => !empty($data['showcase_enabled']),
                        'archived' => !empty($data['archived']),
                        'description' => $data['description'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ];

                    // Gerar ref se vazio
                    if (empty($productData['ref'])) {
                        $productData['ref'] = Product::generateRef($groupId);
                    }

                    // Gerar label_code se vazio
                    if (empty($productData['label_code'])) {
                        $productData['label_code'] = Product::generateLabelCode();
                    }

                    // Buscar produto existente
                    $product = null;
                    if ($ref) {
                        $product = Product::where('ref', $ref)->first();
                    } elseif ($ean13) {
                        $product = Product::where('ean13', $ean13)->first();
                    }

                    if ($product) {
                        $product->update($productData);
                        $updated++;
                    } else {
                        $product = Product::create($productData);
                        $created++;
                    }

                    // Processar preços e estoques por loja
                    $stores = Store::where('active', true)->get();
                    foreach ($stores as $store) {
                        $storeCode = strtolower($store->code);

                        $cost = floatval($data["store:{$storeCode}:cost"] ?? 0);
                        $margin = floatval($data["store:{$storeCode}:margin_percent"] ?? 0);
                        $price = floatval($data["store:{$storeCode}:price"] ?? 0);
                        $location = $data["store:{$storeCode}:location"] ?? null;
                        $qty = intval($data["store:{$storeCode}:qty"] ?? 0);
                        $minQty = intval($data["store:{$storeCode}:min_qty"] ?? 0);

                        // Calcular preço se margin > 0 e price vazio
                        if ($margin > 0 && $price == 0) {
                            $price = round($cost * (1 + $margin / 100), 2);
                        }

                        ProductPrice::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'store_id' => $store->id,
                            ],
                            [
                                'location' => $location,
                                'cost' => $cost,
                                'margin_percent' => $margin,
                                'price' => $price,
                            ]
                        );

                        if ($product->control_stock) {
                            ProductStock::updateOrCreate(
                                [
                                    'product_id' => $product->id,
                                    'store_id' => $store->id,
                                ],
                                [
                                    'qty' => $qty,
                                    'min_qty' => $minQty,
                                ]
                            );
                        }
                    }
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Linha {$rowNum}: " . $e->getMessage();
                }
            }

            DB::commit();

            $message = "Importação concluída! Criados: {$created}, Atualizados: {$updated}, Pulados: {$skipped}";
            if (!empty($errors)) {
                $message .= "\n\nErros:\n" . implode("\n", array_slice($errors, 0, 20));
                if (count($errors) > 20) {
                    $message .= "\n... e mais " . (count($errors) - 20) . " erros.";
                }
            }

            return redirect()->route('products.import.show')
                ->with('success', $message);
        } catch (ReaderException $e) {
            return redirect()->back()
                ->with('error', 'Erro ao ler o arquivo Excel: ' . $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erro durante a importação: ' . $e->getMessage());
        }
    }
}


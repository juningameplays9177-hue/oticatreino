<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Product;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy será implementada depois
    }

    public function rules(): array
    {
        $itemType = $this->input('item_type', 'PRODUTO');
        $isService = $itemType === 'SERVICO';
        
        // Verificar se é tipo Conserto
        $productTypeId = $this->input('product_type_id');
        $isConserto = false;
        if ($productTypeId) {
            $productType = \App\Models\ProductType::find($productTypeId);
            $isConserto = $productType && strtolower($productType->name) === 'conserto';
        }

        return [
            'name' => ['nullable', 'string', 'max:190'],
            'ref' => ['nullable', 'string', 'max:50', 'unique:products,ref'],
            'product_type_id' => ['required', 'exists:product_types,id'],
            'item_type' => ['required', Rule::in(['PRODUTO', 'SERVICO'])],
            'unit' => $isService ? ['nullable', Rule::in(['FR', 'KIT', 'PAR', 'PC', 'UN'])] : ['required', Rule::in(['FR', 'KIT', 'PAR', 'PC', 'UN'])],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'group_id' => ['nullable', 'exists:product_groups,id'],
            'subgroup_id' => ['nullable', 'exists:product_subgroups,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'color' => ['nullable', 'string', 'max:60'],
            'model' => ['nullable', 'string', 'max:100'],
            'description' => $isService ? ['nullable', 'string'] : ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'prices' => ['nullable', 'array'],
            'prices.*.store_id' => ['nullable', 'exists:stores,id'],
            'prices.*.cost' => $isService ? ['nullable'] : ['nullable', 'numeric', 'min:0'],
            'prices.*.margin_percent' => ['nullable', 'numeric', 'between:-99.99,999.99'],
            'prices.*.price' => ['nullable', 'numeric', 'min:0'],
            'prices.*.qty' => $isService ? ['nullable'] : ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'A descrição do produto é obrigatória.',
            'ref.unique' => 'Esta referência já está em uso por outro produto.',
            'unit.required' => 'A unidade é obrigatória.',
            'unit.in' => 'A unidade informada é inválida.',
            'images.*.image' => 'O arquivo deve ser uma imagem.',
            'images.*.mimes' => 'A imagem deve ser do tipo: jpg, jpeg, png ou webp.',
            'images.*.max' => 'A imagem não pode ter mais de 2MB.',
        ];
    }
    
    protected function prepareForValidation(): void
    {
        // Se for serviço e não tiver descrição, usar o nome do tipo de produto como descrição
        $itemType = $this->input('item_type', 'PRODUTO');
        $isService = $itemType === 'SERVICO';
        
        if ($isService && empty($this->input('description'))) {
            $productTypeId = $this->input('product_type_id');
            if ($productTypeId) {
                $productType = \App\Models\ProductType::find($productTypeId);
                if ($productType) {
                    $this->merge([
                        'description' => $productType->name
                    ]);
                }
            }
        }
        
        // Converter valores do formato brasileiro para numérico antes da validação
        if ($this->has('prices')) {
            $prices = $this->input('prices', []);
            foreach ($prices as $storeId => $priceData) {
                // Converter cost (formato brasileiro: 1.234,56 -> 1234.56)
                if (isset($priceData['cost'])) {
                    $prices[$storeId]['cost'] = $this->parseBrazilianNumber($priceData['cost']);
                }
                
                // Converter price (formato brasileiro: 1.234,56 -> 1234.56)
                if (isset($priceData['price'])) {
                    $prices[$storeId]['price'] = $this->parseBrazilianNumber($priceData['price']);
                }
                
                // Converter margin_percent (formato brasileiro: 1.234,56 -> 1234.56)
                if (isset($priceData['margin_percent'])) {
                    $prices[$storeId]['margin_percent'] = $this->parseBrazilianNumber($priceData['margin_percent']);
                }
                
                // Converter qty (já deve ser inteiro, mas garantir)
                if (isset($priceData['qty'])) {
                    $prices[$storeId]['qty'] = $priceData['qty'] === '' ? null : intval($priceData['qty']);
                }
            }
            $this->merge(['prices' => $prices]);
        }
        
        // Se for serviço, remover completamente os dados de prices para evitar validação
        if ($isService && $this->has('prices')) {
            $this->merge([
                'prices' => []
            ]);
        }
    }
    
    /**
     * Converte número em formato brasileiro (1.234,56) para float
     */
    private function parseBrazilianNumber($value)
    {
        if (empty($value) || $value === '' || $value === null) {
            return null;
        }
        
        // Se já for numérico, retornar como está
        if (is_numeric($value)) {
            return floatval($value);
        }
        
        // Remover pontos (separadores de milhar) e substituir vírgula por ponto
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        
        // Se ainda não for numérico, retornar null
        if (!is_numeric($value)) {
            return null;
        }
        
        return floatval($value);
    }
}


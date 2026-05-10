<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Se for admin, garantir que tenha loja selecionada na sessão ou no request
        if (auth()->user()->isAdmin()) {
            $storeId = $this->input('store_id') ?? $this->session()->get('dashboard_store_id');
            if (!$storeId) {
                // Adicionar erro customizado
                $this->merge(['store_id' => null]);
            }
        }
        
        $rules = [
            'store_id' => ['required', 'exists:stores,id'],
            'is_conserto' => ['nullable', 'boolean'],
            'registered_at' => ['nullable', 'date'],
            'employee_id' => ['nullable', 'exists:users,id'],
            'source' => ['nullable', 'string', 'max:80'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:REGISTRADA,EM_PRODUCAO,PRONTA,ENTREGUE,CANCELADA,PERDA,VENDIDA,NAO_VENDIDA'],
            'advance_type' => ['nullable', 'in:SEM,TOTAL,PARCIAL'],
            'advance_value' => ['nullable', 'numeric', 'min:0'],
            'sinal_amount' => ['nullable', 'numeric', 'min:0'],
            'sinal_method' => ['nullable', 'string', 'in:money,pix,card_credit,card_debit'],
            'payment_type' => ['required', 'string', 'in:avista,sinal,parcelado'],
            'payment_method' => ['required', 'string', 'in:money,pix,card_credit,card_debit,boleto,carne'],
            'carne_parcelas_count' => ['nullable', 'integer', 'min:1', 'max:24'],
            'parcelas_count' => ['nullable', 'integer', 'min:1', 'max:12'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'items' => ['nullable', 'array'], // Permite OS sem itens
            'items.*.type' => ['required_with:items', 'in:PRODUTO,SERVICO'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.name' => ['required_with:items', 'string', 'max:190'],
            'items.*.ref' => ['nullable', 'string', 'max:50'],
            'items.*.unit' => ['nullable', 'string', 'max:10'],
            'items.*.qty' => ['required_with:items', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.price_adjust' => ['nullable', 'numeric'],
            'items.*.add_disc_percent' => ['nullable', 'numeric', 'between:-99.99,999.99'],
            'items.*.barcode' => ['nullable', 'string', 'max:20'],
            'prescription' => ['nullable', 'array'],
            'prescription.prescription_id' => ['nullable', 'exists:prescriptions,id'],
            'prescription.use_custom' => ['nullable', 'boolean'],
            'prescription.custom_doctor_name' => ['nullable', 'string', 'max:190'],
            'prescription.custom_adicao' => ['nullable', 'string', 'max:50'],
            'prescription.custom_notes' => ['nullable', 'string'],
            // Longe OD
            'prescription.custom_longe_esferico_od' => ['nullable', 'string', 'max:50'],
            'prescription.custom_longe_cilindrico_od' => ['nullable', 'string', 'max:50'],
            'prescription.custom_longe_eixo_od' => ['nullable', 'string', 'max:50'],
            'prescription.custom_longe_altura_od' => ['nullable', 'string', 'max:50'],
            'prescription.custom_longe_dnp_od' => ['nullable', 'string', 'max:50'],
            // Longe OE
            'prescription.custom_longe_esferico_oe' => ['nullable', 'string', 'max:50'],
            'prescription.custom_longe_cilindrico_oe' => ['nullable', 'string', 'max:50'],
            'prescription.custom_longe_eixo_oe' => ['nullable', 'string', 'max:50'],
            'prescription.custom_longe_altura_oe' => ['nullable', 'string', 'max:50'],
            'prescription.custom_longe_dnp_oe' => ['nullable', 'string', 'max:50'],
            // Perto OD
            'prescription.custom_perto_esferico_od' => ['nullable', 'string', 'max:50'],
            'prescription.custom_perto_cilindrico_od' => ['nullable', 'string', 'max:50'],
            'prescription.custom_perto_eixo_od' => ['nullable', 'string', 'max:50'],
            'prescription.custom_perto_altura_od' => ['nullable', 'string', 'max:50'],
            'prescription.custom_perto_dnp_od' => ['nullable', 'string', 'max:50'],
            // Perto OE
            'prescription.custom_perto_esferico_oe' => ['nullable', 'string', 'max:50'],
            'prescription.custom_perto_cilindrico_oe' => ['nullable', 'string', 'max:50'],
            'prescription.custom_perto_eixo_oe' => ['nullable', 'string', 'max:50'],
            'prescription.custom_perto_altura_oe' => ['nullable', 'string', 'max:50'],
            'prescription.custom_perto_dnp_oe' => ['nullable', 'string', 'max:50'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'prescription_attachment' => ['nullable', 'file', 'mimes:png,jpg,jpeg,gif,tiff,pdf', 'max:5120'],
        ];

        $isConserto = $this->boolean('is_conserto', false);
        if ($isConserto) {
            $rules['conserto_client_name'] = ['required', 'string', 'max:190'];
            $rules['conserto_client_contact'] = ['nullable', 'string', 'max:100'];
            $rules['client_id'] = ['nullable', 'exists:clients,id'];
        } else {
            $rules['client_id'] = ['required', 'exists:clients,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'store_id.required' => 'A loja é obrigatória. Por favor, selecione uma loja no dashboard antes de criar a OS.',
            'store_id.exists' => 'A loja selecionada não existe ou está inativa.',
            'client_id.required' => 'O cliente é obrigatório.',
            'conserto_client_name.required' => 'O nome do cliente é obrigatório no modo Conserto.',
            'delivery_date.after_or_equal' => 'A data de entrega não pode ser anterior a hoje.',
        ];
        
        // Mensagem especial para admin sem loja selecionada
        if (auth()->user()->isAdmin() && !$this->input('store_id') && !$this->session()->get('dashboard_store_id')) {
            $messages['store_id.required'] = '⚠️ Você precisa selecionar uma loja no dashboard antes de criar uma OS. <a href="' . route('dashboard') . '" class="underline">Ir para Dashboard</a>';
        }
        
        return $messages;
    }
    
    protected function prepareForValidation()
    {
        // Se for admin e não tiver store_id no request, tentar buscar da sessão
        if (auth()->user()->isAdmin() && !$this->has('store_id')) {
            $storeId = $this->session()->get('dashboard_store_id');
            if ($storeId) {
                $this->merge(['store_id' => $storeId]);
            }
        }
    }
}


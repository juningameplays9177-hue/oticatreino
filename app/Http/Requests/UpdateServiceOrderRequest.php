<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'source' => ['nullable', 'string', 'max:80'],
            'delivery_date' => ['nullable', 'date'],
            'delivery_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'exists:service_order_items,id'],
            'items.*.type' => ['required', 'in:PRODUTO,SERVICO'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.name' => ['required', 'string', 'max:190'],
            'items.*.ref' => ['nullable', 'string', 'max:50'],
            'items.*.unit' => ['nullable', 'string', 'max:10'],
            'items.*.qty' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.price_adjust' => ['nullable', 'numeric'],
            'items.*.add_disc_percent' => ['nullable', 'numeric', 'between:-99.99,999.99'],
            'items.*.barcode' => ['nullable', 'string', 'max:20'],
            'prescription' => ['nullable', 'array'],
            'prescription.prescription_id' => ['nullable', 'exists:prescriptions,id'],
            'prescription.use_custom' => ['nullable', 'boolean'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'O cliente é obrigatório.',
            'items.required' => 'Adicione pelo menos um item na O.S.',
            'items.min' => 'Adicione pelo menos um item na O.S.',
        ];
    }
}


<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Client;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $authorized = auth()->check();
        
        // Log para debug (usando sistema de logs do Laravel)
        try {
            \Log::info('StoreClientRequest::authorize', [
                'authorized' => $authorized,
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            // Ignorar erros de log para não quebrar a aplicação
        }
        
        return $authorized;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'active' => 'boolean',
            'name' => 'required|string|max:190',
            'cpf_cnpj' => [
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $normalized = Client::normalizeCpfCnpj($value);
                        $length = strlen($normalized);
                        
                        // Detectar automaticamente se é CPF ou CNPJ pelo tamanho
                        if ($length == 11) {
                            // Validar CPF
                            if (!Client::validateCpf($normalized)) {
                                $fail('O CPF informado é inválido.');
                            }
                        } elseif ($length == 14) {
                            // Validar CNPJ
                            if (!Client::validateCnpj($normalized)) {
                                $fail('O CNPJ informado é inválido.');
                            }
                        } elseif ($length > 0) {
                            $fail('O CPF/CNPJ deve conter 11 dígitos (CPF) ou 14 dígitos (CNPJ).');
                        }
                    }
                },
            ],
            'birth_date' => 'nullable|date|before_or_equal:today',
            'cep' => 'nullable|string|max:9',
            'city' => 'nullable|string|max:120',
            'district' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:190',
            'number' => 'nullable|string|max:20',
            'complement' => 'nullable|string|max:120',
            'state' => 'nullable|string|max:2',
            'notes' => 'nullable|string',
            
            // Arrays para telefones e e-mails
            'phones' => 'nullable|array',
            'phones.*.phone' => 'nullable|string|max:30',
            'phones.*.label' => 'nullable|string|max:40',
            
            'emails' => 'nullable|array',
            'emails.*.email' => 'nullable|email|max:190',
            'emails.*.label' => 'nullable|string|max:40',
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'cpf_cnpj.unique' => 'Este CPF/CNPJ já está cadastrado.',
            'emails.*.email.email' => 'Um ou mais e-mails são inválidos.',
        ];
    }
    
    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // Log do erro de validação
        \Log::error('StoreClientRequest: Validação falhou', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['password', '_token']),
        ]);
        
        throw new \Illuminate\Validation\ValidationException($validator);
    }
}

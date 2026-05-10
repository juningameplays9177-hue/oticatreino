<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Client;

class UpdateClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $clientId = $this->route('client')->id;
        
        $rules = [
            'active' => 'boolean',
            'name' => 'required|string|max:190',
            'cpf_cnpj' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('clients', 'cpf_cnpj')->ignore($clientId),
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
            'phones.*.phone.required_with' => 'O telefone é obrigatório quando o array de telefones está presente.',
            'emails.*.email.required_with' => 'O e-mail é obrigatório quando o array de e-mails está presente.',
        ];
    }
    
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Filtrar arrays vazios de telefones e e-mails antes da validação
        if ($this->has('phones')) {
            $phones = array_filter($this->input('phones', []), function($phone) {
                return !empty($phone['phone']);
            });
            $this->merge(['phones' => array_values($phones)]);
        }
        
        if ($this->has('emails')) {
            $emails = array_filter($this->input('emails', []), function($email) {
                return !empty($email['email']);
            });
            $this->merge(['emails' => array_values($emails)]);
        }
    }
}

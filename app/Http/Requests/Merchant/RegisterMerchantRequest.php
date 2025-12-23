<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class RegisterMerchantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', 'max:100'],
            'business_registration_number' => ['required', 'string', 'max:100'],
            'tax_id' => ['required', 'string', 'max:50'],
            'business_email' => ['required', 'email', 'max:255'],
            'business_phone' => ['required', 'string', 'max:20'],
            'business_address' => ['required', 'string'],
            'documents' => ['nullable', 'array'],
        ];
    }
}

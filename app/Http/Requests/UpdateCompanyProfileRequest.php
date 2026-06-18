<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isVatPayer = $this->boolean('is_vat_payer');

        return [
            'company_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'ico' => ['nullable', 'string', 'max:20'],
            'dic' => [$isVatPayer ? 'required' : 'nullable', 'string', 'max:20'],
            'bank_account' => ['nullable', 'string', 'regex:/^(?:\d{1,6}-)?\d{1,10}\/\d{4}$/'],
            'is_vat_payer' => ['boolean'],
        ];
    }
}

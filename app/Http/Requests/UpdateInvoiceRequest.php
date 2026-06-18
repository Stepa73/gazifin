<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends StoreInvoiceRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['number'] = [
            'required',
            'string',
            'max:50',
            Rule::unique('invoices', 'number')
                ->where('user_id', auth()->id())
                ->ignore($this->route('invoice')),
        ];

        return $rules;
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncomePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientIds = $this->user()->clients()->pluck('id')->all();

        return [
            'regime' => ['required', Rule::in(['auto', 'pausal', 'klasik'])],
            'sideActivity' => ['required', 'boolean'],
            'activity' => ['required', Rule::in(['40', '60', '80'])],
            'expMode' => ['required', Rule::in(['pausal', 'real'])],
            'expReal' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'carryAmount' => ['required', 'numeric', 'min:-999999999', 'max:999999999'],
            'carryMonth' => ['required', 'integer', 'between:0,11'],

            'sources' => ['present', 'array', 'max:50'],
            'sources.*.clientId' => ['nullable', Rule::in($clientIds)],
            'sources.*.name' => ['required', 'string', 'max:255'],
            'sources.*.mode' => ['required', Rule::in(['rate', 'fixed', 'invoice'])],
            'sources.*.rate' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'sources.*.unit' => ['required', Rule::in(['h', 'md'])],
            'sources.*.hoursPerDay' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'sources.*.lag' => ['required', 'integer', 'between:0,3'],
            'sources.*.payDay' => ['required', 'integer', 'between:1,31'],
            'sources.*.from' => ['nullable', 'date'],
            'sources.*.to' => ['nullable', 'date'],
            'sources.*.fixed' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'sources.*.date' => ['nullable', 'date'],
            'sources.*.amount' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'sources.*.vacation' => ['required', 'array', 'size:12'],
            'sources.*.vacation.*' => ['required', 'integer', 'between:0,31'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Prohlížeč posílá prázdná data jako '', validátor na ně chce null.
        $sources = collect($this->input('sources', []))
            ->map(function ($source) {
                foreach (['from', 'to', 'date'] as $field) {
                    if (($source[$field] ?? null) === '') {
                        $source[$field] = null;
                    }
                }

                if (($source['clientId'] ?? null) === '') {
                    $source['clientId'] = null;
                }

                return $source;
            })
            ->all();

        $this->merge(['sources' => $sources]);
    }
}

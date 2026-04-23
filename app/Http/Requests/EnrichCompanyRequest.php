<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrichCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain'       => ['nullable', 'string', 'max:253'],
            'company_name' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->domain) && empty($this->company_name)) {
                $validator->errors()->add('domain', 'Either domain or company_name must be provided.');
            }
        });
    }
}

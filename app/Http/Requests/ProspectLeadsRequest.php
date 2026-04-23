<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProspectLeadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_title'       => ['nullable', 'string', 'max:100'],
            'company_name'    => ['nullable', 'string', 'max:150'],
            'country'         => ['nullable', 'string', 'size:2'],
            'industry'        => ['nullable', 'string', 'max:100'],
            'company_size'    => ['nullable', 'string', 'max:20'],
            'department'      => ['nullable', 'string', 'max:100'],
            'seniority_level' => ['nullable', 'string', 'max:50'],
            'page'            => ['nullable', 'integer', 'min:1'],
            'page_size'       => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}

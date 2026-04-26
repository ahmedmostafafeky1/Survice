<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrichPersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name'  => ['required', 'string', 'max:80'],
            'company'    => ['required', 'string', 'max:150'],
        ];
    }
}

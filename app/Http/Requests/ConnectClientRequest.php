<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConnectClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'app_name.required' => 'اسم التطبيق مطلوب.',
            'app_name.max' => 'اسم التطبيق يجب ألا يتجاوز 100 حرف.',
        ];
    }
}

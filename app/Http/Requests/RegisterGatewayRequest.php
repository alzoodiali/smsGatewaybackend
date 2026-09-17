<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'device_id' => ['required', 'string', 'max:100'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الـ Gateway مطلوب.',
            'device_id.required' => 'معرّف الجهاز مطلوب.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone') && ! $this->has('phone_number')) {
            $this->merge(['phone_number' => $this->input('phone')]);
        } elseif ($this->has('phone_number') && ! $this->has('phone')) {
            $this->merge(['phone' => $this->input('phone_number')]);
        }
    }

    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string'],
            'purpose' => ['nullable', 'string', 'max:50'],
        ];
    }
}

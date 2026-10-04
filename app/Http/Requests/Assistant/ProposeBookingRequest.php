<?php

namespace App\Http\Requests\Assistant;

use Illuminate\Foundation\Http\FormRequest;

class ProposeBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('assistant.use');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:500'],
            'confirm' => ['sometimes', 'boolean'],
            'customer_name' => ['required_if:confirm,true', 'nullable', 'string', 'max:255'],
            'customer_email' => ['required_if:confirm,true', 'nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}

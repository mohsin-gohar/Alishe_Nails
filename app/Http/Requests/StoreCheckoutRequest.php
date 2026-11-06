<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:150'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['required', 'string', 'in:online_payment,jazzcash,sadapay,easypaisa,bank_transfer'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'instagram_username' => ['nullable', 'string', 'max:100'],
            'sender_number' => ['nullable', 'string', 'max:50'],
            'payment_notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Please select your online payment method (JazzCash or SadaPay).',
            'payment_method.in' => 'Please select either JazzCash or SadaPay for advance payment.',
        ];
    }
}

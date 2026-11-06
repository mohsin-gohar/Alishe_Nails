<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qty' => ['required', 'integer', 'min:1', 'max:200'],
            'shape' => ['nullable', 'string', 'max:50'],
            'size' => ['nullable', 'string', 'max:150'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required_without:guest', 'integer', 'exists:customers,id'],
            'guest' => ['required_without:customer_id', 'array'],
            'guest.email' => ['required_with:guest', 'email', 'max:190'],
            'guest.name' => ['required_with:guest', 'string', 'max:120'],
            'guest.company' => ['required_with:guest', 'string', 'max:190'],
            'guest.address' => ['required_with:guest', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'discount_code' => ['nullable', 'string', 'max:50'],
        ];
    }
}

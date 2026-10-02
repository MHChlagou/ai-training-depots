<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiscountTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO séance 4 : restreindre aux administrateurs (policy ou gate).
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'min_subtotal_cents' => ['required', 'integer', 'min:1', 'unique:discount_tiers,min_subtotal_cents'],
            'rate_percent' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'lte:50'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'min_subtotal_cents.unique' => 'Un palier existe déjà pour ce seuil.',
            'rate_percent.lte' => 'Le taux ne peut pas dépasser 50 %.',
        ];
    }
}

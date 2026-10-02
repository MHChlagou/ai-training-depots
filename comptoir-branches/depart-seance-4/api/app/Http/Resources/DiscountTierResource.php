<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'min_subtotal_cents' => $this->min_subtotal_cents,
            'rate_percent' => $this->rate_bp / 100,
            'active' => $this->active,
        ];
    }
}

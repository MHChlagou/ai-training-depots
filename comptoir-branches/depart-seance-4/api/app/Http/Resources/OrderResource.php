<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'subtotal_cents' => $this->subtotal_cents,
            'discount_cents' => $this->discount_cents,
            'discount_tier_id' => $this->discount_tier_id,
            'total_cents' => $this->total_cents,
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'company' => $this->customer->company,
            ],
            'lines_count' => $this->lines->count(),
            'lines' => OrderLineResource::collection($this->lines),
        ];
    }
}

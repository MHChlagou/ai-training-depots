<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'reference',
        'status',
        'total_cents',
        'placed_at',
        'shipping_address',
        'discount_code',
    ];

    protected function casts(): array
    {
        return [
            'placed_at' => 'datetime',
            'total_cents' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->reference)) {
                $order->reference = static::generateReference();
            }
        });
    }

    /**
     * Référence lisible par le client : « CMD- » suivi de 8 chiffres, unique.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'CMD-'.random_int(10000000, 99999999);
        } while (static::query()->where('reference', $reference)->exists());

        return $reference;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discount_code', 'code');
    }
}

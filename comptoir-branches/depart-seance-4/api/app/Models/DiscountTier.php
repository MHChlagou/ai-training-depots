<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountTier extends Model
{
    use HasFactory;

    protected $fillable = ['label', 'min_subtotal_cents', 'rate_bp', 'active'];

    protected function casts(): array
    {
        return [
            'min_subtotal_cents' => 'integer',
            'rate_bp' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * Palier actif le plus élevé dont le seuil est atteint (seuil inclusif), ou null.
     */
    public static function applicableTo(int $subtotalCents): ?self
    {
        return static::query()
            ->active()
            ->where('min_subtotal_cents', '<=', $subtotalCents)
            ->orderByDesc('min_subtotal_cents')
            ->first();
    }

    /**
     * Montant de remise en centimes, arrondi au centime le plus proche (demi vers le haut), en arithmétique entière.
     */
    public function discountFor(int $subtotalCents): int
    {
        return intdiv($subtotalCents * $this->rate_bp + 5000, 10000);
    }
}

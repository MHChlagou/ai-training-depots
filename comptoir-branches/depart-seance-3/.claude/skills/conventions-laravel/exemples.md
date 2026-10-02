# Exemples commentés : conventions Laravel de comptoir

Chaque exemple montre d'abord ce qu'il faut éviter, puis la forme attendue.

## 1. Contrôleur mince

À éviter : validation, calcul et requêtes dans le contrôleur.

```php
public function store(Request $request)
{
    $request->validate(['customer_id' => 'required', 'lines' => 'required|array']);
    $order = Order::create(['customer_id' => $request->customer_id, 'total' => 0]);
    $total = 0;
    foreach ($request->lines as $line) {
        $product = Product::find($line['product_id']);          // une requête par ligne
        $total += $product->price * $line['quantity'] * 1.2;     // float + TVA en dur
        $order->lines()->create($line);
    }
    $order->update(['total' => round($total, 2)]);
    return $order;                                              // modèle brut en JSON
}
```

Attendu : le contrôleur orchestre, chaque responsabilité a sa classe.

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

final class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orders->place($request->validated());

        return OrderResource::make($order->load(['lines.product', 'customer']))
            ->response()
            ->setStatusCode(201);
    }
}
```

## 2. Service avec transaction et montants en centimes

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    /**
     * @param  array{customer_id: int, lines: list<array{product_id: int, quantity: int}>}  $data
     */
    public function place(array $data): Order
    {
        return DB::transaction(function () use ($data): Order {
            $products = Product::query()
                ->whereIn('id', array_column($data['lines'], 'product_id'))
                ->get()
                ->keyBy('id');                                   // une seule requête

            $order = Order::create(['customer_id' => $data['customer_id'], 'total_cents' => 0]);

            $total = 0;
            foreach ($data['lines'] as $line) {
                $unit = $products[$line['product_id']]->price_cents;
                $order->lines()->create([
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_price_cents' => $unit,
                ]);
                $total += $unit * $line['quantity'];              // entiers, pas d'arrondi intermédiaire
            }

            $order->update(['total_cents' => $total]);

            return $order;
        });
    }
}
```

> Note : l'exemple utilise la façade `DB` pour la lisibilité. Pour un service très testé,
> injectez `Illuminate\Database\ConnectionInterface` à la place.

## 3. Éviter le N+1

À éviter :

```php
$orders = Order::latest()->paginate(20);
foreach ($orders as $order) {
    echo $order->customer->name;            // 1 requête par commande
    echo $order->lines->count();            // encore 1 requête par commande
}
```

Attendu :

```php
$orders = Order::query()
    ->with('customer')
    ->withCount('lines')
    ->latest()
    ->paginate(20);
```

## 4. FormRequest

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // remplacer par une Policy quand l'authentification est en place
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
```

## 5. Test Pest de fonctionnalité

```php
<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Product;

it('crée une commande et calcule le total en centimes', function () {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['price_cents' => 1250]);

    $this->postJson('/api/orders', [
        'customer_id' => $customer->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 2]],
    ])
        ->assertCreated()
        ->assertJsonPath('data.total_cents', 2500);
});

it('refuse une commande sans ligne', function () {
    $customer = Customer::factory()->create();

    $this->postJson('/api/orders', ['customer_id' => $customer->id, 'lines' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lines']);
});
```

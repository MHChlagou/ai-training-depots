<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * GET /api/products
     */
    public function index(): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with('category')
            ->orderBy('name')
            ->get();

        return ProductResource::collection($products);
    }

    /**
     * GET /api/products/{slug}
     */
    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product->load('category'));
    }
}

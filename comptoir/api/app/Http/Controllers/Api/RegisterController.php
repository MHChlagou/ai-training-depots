<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /**
     * POST /api/register
     */
    public function store(RegisterCustomerRequest $request): JsonResponse
    {
        $customer = new Customer($request->safe()->only(['name', 'company', 'email', 'password']));
        $customer->type = 'pro';
        $customer->country = 'FR';
        $customer->save();

        return CustomerResource::make($customer)
            ->response()
            ->setStatusCode(201);
    }
}

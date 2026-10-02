<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * GET /api/me
     */
    public function show(Request $request): CustomerResource
    {
        return CustomerResource::make($request->user());
    }
}

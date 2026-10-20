<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CustomerController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return CustomerResource::collection(
            Customer::query()->orderBy('name')->paginate($this->perPage($request))->withQueryString(),
        );
    }

    public function store(CustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer);
    }

    public function update(CustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer, CustomerService $customers): Response
    {
        $customers->delete($customer);

        return response()->noContent();
    }
}

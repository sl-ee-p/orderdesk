<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CustomerController extends Controller
{
    public function index(): View
    {
        return view('customers.index', [
            'customers' => Customer::query()->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('customers.create', ['customer' => new Customer]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        return redirect()->route('customers.show', $customer)->with('status', 'Customer created.');
    }

    public function show(Customer $customer): View
    {
        return view('customers.show', [
            'customer' => $customer,
            'quotes' => $customer->quotes()->latest()->limit(20)->get(),
            'orders' => $customer->orders()->latest()->limit(20)->get(),
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', ['customer' => $customer]);
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('customers.show', $customer)->with('status', 'Customer updated.');
    }

    public function destroy(Customer $customer, CustomerService $customers): RedirectResponse
    {
        $customers->delete($customer);

        return redirect()->route('customers.index')->with('status', 'Customer deleted.');
    }
}

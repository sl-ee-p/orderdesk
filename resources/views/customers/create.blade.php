<x-layout title="New customer">
    <x-page-header title="New customer" />
    @include('customers._form', ['action' => route('customers.store')])
</x-layout>

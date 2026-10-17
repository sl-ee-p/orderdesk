<x-layout title="Edit customer">
    <x-page-header title="Edit {{ $customer->name }}" />
    @include('customers._form', ['action' => route('customers.update', $customer), 'method' => 'PUT'])
</x-layout>

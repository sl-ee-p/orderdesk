<x-layout title="New product">
    <x-page-header title="New product" />
    @include('products._form', ['action' => route('products.store')])
</x-layout>

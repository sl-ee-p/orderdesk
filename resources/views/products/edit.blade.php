<x-layout title="Edit product">
    <x-page-header title="Edit {{ $product->name }}">
        <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
            @csrf @method('DELETE')
            <x-button variant="danger">Delete</x-button>
        </form>
    </x-page-header>
    @include('products._form', ['action' => route('products.update', $product), 'method' => 'PUT'])
</x-layout>

<x-layout title="Products">
    <x-page-header title="Products">
        <x-button :href="route('products.create')">New product</x-button>
    </x-page-header>

    <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr><th scope="col" class="px-4 py-2">SKU</th><th scope="col" class="px-4 py-2">Name</th><th scope="col" class="px-4 py-2 text-right">Unit price</th><th scope="col" class="px-4 py-2">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-4 py-2">{{ $product->sku }}</td>
                        <td class="px-4 py-2"><a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:underline">{{ $product->name }}</a></td>
                        <td class="px-4 py-2 text-right">@money($product->unit_price_cents)</td>
                        <td class="px-4 py-2">{{ $product->is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No products yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $products->links() }}</div>
</x-layout>

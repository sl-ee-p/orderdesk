<form method="POST" action="{{ $action }}" class="max-w-xl space-y-4 rounded-lg bg-white p-6 shadow">
    @csrf
    @isset($method) @method($method) @endisset
    <x-field name="sku" label="SKU" :value="$product->sku" required />
    <x-field name="name" label="Name" :value="$product->name" required />
    <x-field name="description" label="Description" :value="$product->description" />
    <x-field name="unit_price" label="Unit price" inputmode="decimal" :value="$product->exists ? number_format($product->unit_price_cents / 100, 2, '.', '') : null" required />
    <label class="flex items-center gap-2 text-sm">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active))> Active (can be added to new quotes)
    </label>
    <div class="flex gap-2">
        <x-button>Save</x-button>
        <x-button variant="secondary" :href="route('products.index')">Cancel</x-button>
    </div>
</form>

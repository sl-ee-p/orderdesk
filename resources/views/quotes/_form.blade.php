@php
    $lines = old('items', $quote->exists
        ? $quote->items->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity])->all()
        : [['product_id' => '', 'quantity' => 1]]);
    $taxRate = old('tax_rate', \App\Support\Money::basisPointsToPercent($quote->tax_rate_bp ?? 0));
@endphp
<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @isset($method) @method($method) @endisset

    <div class="grid max-w-3xl gap-4 rounded-lg bg-white p-6 shadow md:grid-cols-2">
        <div>
            <label for="customer_id" class="block text-sm font-medium text-slate-700">Customer</label>
            <select id="customer_id" name="customer_id" required class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Choose…</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('customer_id', $quote->customer_id) == $customer->id)>{{ $customer->name }}{{ $customer->company ? ' ('.$customer->company.')' : '' }}</option>
                @endforeach
            </select>
            @error('customer_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <x-field name="tax_rate" label="Tax rate (%)" inputmode="decimal" :value="$taxRate" required />
        <x-field name="valid_until" label="Valid until" type="date" :value="$quote->valid_until?->format('Y-m-d')" />
        <x-field name="notes" label="Notes" :value="$quote->notes" />
    </div>

    <div class="max-w-3xl rounded-lg bg-white p-6 shadow">
        <h2 class="mb-3 text-lg font-semibold">Line items</h2>
        @error('items') <p class="mb-2 text-sm text-red-600">{{ $message }}</p> @enderror
        <div id="lines" class="space-y-2">
            @foreach ($lines as $i => $line)
                <div class="line flex items-start gap-2">
                    <select name="items[{{ $i }}][product_id]" required class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Choose a product…</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(($line['product_id'] ?? '') == $product->id)>{{ $product->name }} — @money($product->unit_price_cents)</option>
                        @endforeach
                    </select>
                    <input type="number" min="1" name="items[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" required class="w-24 rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <button type="button" data-remove class="px-2 py-2 text-sm text-red-600">Remove</button>
                </div>
                @error("items.$i.product_id") <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @error("items.$i.quantity") <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @endforeach
        </div>
        <button type="button" id="add-line" class="mt-3 text-sm text-indigo-600 hover:underline">+ Add line</button>
    </div>

    <div class="flex gap-2">
        <x-button>Save quote</x-button>
        <x-button variant="secondary" :href="route('quotes.index')">Cancel</x-button>
    </div>
</form>

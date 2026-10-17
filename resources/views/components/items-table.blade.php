@props(['document'])
<table class="min-w-full divide-y divide-slate-200 text-sm">
    <thead class="bg-slate-50 text-left text-slate-500">
        <tr><th class="px-4 py-2">Item</th><th class="px-4 py-2 text-right">Qty</th><th class="px-4 py-2 text-right">Unit price</th><th class="px-4 py-2 text-right">Total</th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
        @foreach ($document->items as $item)
            <tr>
                <td class="px-4 py-2">{{ $item->description }}</td>
                <td class="px-4 py-2 text-right">{{ $item->quantity }}</td>
                <td class="px-4 py-2 text-right">@money($item->unit_price_cents)</td>
                <td class="px-4 py-2 text-right">@money($item->line_total_cents)</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot class="text-right">
        <tr><td colspan="3" class="px-4 py-1 text-slate-500">Subtotal</td><td class="px-4 py-1">@money($document->subtotal_cents)</td></tr>
        <tr><td colspan="3" class="px-4 py-1 text-slate-500">Tax ({{ \App\Support\Money::basisPointsToPercent($document->tax_rate_bp) }}%)</td><td class="px-4 py-1">@money($document->tax_cents)</td></tr>
        <tr class="font-semibold"><td colspan="3" class="px-4 py-2">Total</td><td class="px-4 py-2">@money($document->total_cents)</td></tr>
    </tfoot>
</table>

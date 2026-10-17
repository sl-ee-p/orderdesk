<x-layout title="Quotes">
    <x-page-header title="Quotes">
        <x-button :href="route('quotes.create')">New quote</x-button>
    </x-page-header>

    <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr><th class="px-4 py-2">Number</th><th class="px-4 py-2">Customer</th><th class="px-4 py-2 text-right">Total</th><th class="px-4 py-2">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($quotes as $quote)
                    <tr>
                        <td class="px-4 py-2"><a href="{{ route('quotes.show', $quote) }}" class="text-indigo-600 hover:underline">{{ $quote->number }}</a></td>
                        <td class="px-4 py-2">{{ $quote->customer->name }}</td>
                        <td class="px-4 py-2 text-right">@money($quote->total_cents)</td>
                        <td class="px-4 py-2"><x-status-badge :status="$quote->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No quotes yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $quotes->links() }}</div>
</x-layout>

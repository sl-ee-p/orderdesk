<x-layout title="Orders">
    <x-page-header title="Orders" />

    <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr><th scope="col" class="px-4 py-2">Number</th><th scope="col" class="px-4 py-2">Customer</th><th scope="col" class="px-4 py-2 text-right">Total</th><th scope="col" class="px-4 py-2">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-2"><a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:underline">{{ $order->number }}</a></td>
                        <td class="px-4 py-2">{{ $order->customer->name }}</td>
                        <td class="px-4 py-2 text-right">@money($order->total_cents)</td>
                        <td class="px-4 py-2"><x-status-badge :status="$order->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No orders yet. Accept a quote and convert it.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</x-layout>

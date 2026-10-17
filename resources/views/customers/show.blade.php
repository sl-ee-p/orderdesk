<x-layout :title="$customer->name">
    <x-page-header :title="$customer->name">
        <x-button variant="secondary" :href="route('customers.edit', $customer)">Edit</x-button>
        <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')">
            @csrf @method('DELETE')
            <x-button variant="danger">Delete</x-button>
        </form>
    </x-page-header>

    <dl class="mb-8 grid max-w-xl grid-cols-3 gap-2 rounded-lg bg-white p-6 text-sm shadow">
        <dt class="text-slate-500">Email</dt><dd class="col-span-2">{{ $customer->email }}</dd>
        <dt class="text-slate-500">Phone</dt><dd class="col-span-2">{{ $customer->phone }}</dd>
        <dt class="text-slate-500">Company</dt><dd class="col-span-2">{{ $customer->company }}</dd>
        <dt class="text-slate-500">Address</dt><dd class="col-span-2">{{ $customer->address }}</dd>
    </dl>

    <h2 class="mb-2 text-lg font-semibold">Quotes</h2>
    <ul class="mb-6 divide-y divide-slate-100 rounded-lg bg-white text-sm shadow">
        @forelse ($quotes as $quote)
            <li class="flex items-center justify-between px-4 py-2">
                <a href="{{ route('quotes.show', $quote) }}" class="text-indigo-600 hover:underline">{{ $quote->number }}</a>
                <span>@money($quote->total_cents) <x-status-badge :status="$quote->status" /></span>
            </li>
        @empty
            <li class="px-4 py-3 text-slate-500">No quotes.</li>
        @endforelse
    </ul>

    <h2 class="mb-2 text-lg font-semibold">Orders</h2>
    <ul class="divide-y divide-slate-100 rounded-lg bg-white text-sm shadow">
        @forelse ($orders as $order)
            <li class="flex items-center justify-between px-4 py-2">
                <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:underline">{{ $order->number }}</a>
                <span>@money($order->total_cents) <x-status-badge :status="$order->status" /></span>
            </li>
        @empty
            <li class="px-4 py-3 text-slate-500">No orders.</li>
        @endforelse
    </ul>
</x-layout>

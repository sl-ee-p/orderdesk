<x-layout :title="$order->number">
    <x-page-header :title="$order->number">
        @if ($order->status->canTransitionTo(\App\Enums\OrderStatus::Confirmed))
            <x-status-button :action="route('orders.status', $order)" status="confirmed">Confirm</x-status-button>
        @endif
        @if ($order->status->canTransitionTo(\App\Enums\OrderStatus::Shipped))
            <x-status-button :action="route('orders.status', $order)" status="shipped">Mark as shipped</x-status-button>
        @endif
        @if ($order->status->canTransitionTo(\App\Enums\OrderStatus::Cancelled))
            <x-status-button :action="route('orders.status', $order)" status="cancelled" variant="danger">Cancel order</x-status-button>
        @endif
    </x-page-header>

    <dl class="mb-6 grid max-w-xl grid-cols-3 gap-2 rounded-lg bg-white p-6 text-sm shadow">
        <dt class="text-slate-500">Status</dt><dd class="col-span-2"><x-status-badge :status="$order->status" /></dd>
        <dt class="text-slate-500">Customer</dt><dd class="col-span-2"><a href="{{ route('customers.show', $order->customer) }}" class="text-indigo-600 hover:underline">{{ $order->customer->name }}</a></dd>
        @if ($order->quote)
            <dt class="text-slate-500">From quote</dt><dd class="col-span-2"><a href="{{ route('quotes.show', $order->quote) }}" class="text-indigo-600 hover:underline">{{ $order->quote->number }}</a></dd>
        @endif
        <dt class="text-slate-500">Confirmed</dt><dd class="col-span-2">{{ $order->confirmed_at?->format('Y-m-d H:i') ?? '—' }}</dd>
        <dt class="text-slate-500">Shipped</dt><dd class="col-span-2">{{ $order->shipped_at?->format('Y-m-d H:i') ?? '—' }}</dd>
        <dt class="text-slate-500">Cancelled</dt><dd class="col-span-2">{{ $order->cancelled_at?->format('Y-m-d H:i') ?? '—' }}</dd>
        <dt class="text-slate-500">Notes</dt><dd class="col-span-2">{{ $order->notes }}</dd>
    </dl>

    <div class="overflow-hidden rounded-lg bg-white shadow"><x-items-table :document="$order" /></div>
</x-layout>

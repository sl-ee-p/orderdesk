@php($expired = $quote->isExpired() && in_array($quote->status, [\App\Enums\QuoteStatus::Draft, \App\Enums\QuoteStatus::Sent], true))
<x-layout :title="$quote->number">
    <x-page-header :title="$quote->number">
        @if ($quote->status->isEditable())
            <x-button variant="secondary" :href="route('quotes.edit', $quote)">Edit</x-button>
        @endif
        @if ($quote->status->canTransitionTo(\App\Enums\QuoteStatus::Sent) && ! $expired)
            <x-status-button :action="route('quotes.status', $quote)" status="sent">Mark as sent</x-status-button>
        @endif
        @if ($quote->status->canTransitionTo(\App\Enums\QuoteStatus::Accepted) && ! $expired)
            <x-status-button :action="route('quotes.status', $quote)" status="accepted">Accept</x-status-button>
        @endif
        @if ($quote->status->canTransitionTo(\App\Enums\QuoteStatus::Rejected))
            <x-status-button :action="route('quotes.status', $quote)" status="rejected" variant="danger">Reject</x-status-button>
        @endif
        @if ($quote->status === \App\Enums\QuoteStatus::Accepted && ! $quote->order)
            <form method="POST" action="{{ route('quotes.convert', $quote) }}">
                @csrf
                <x-button>Convert to order</x-button>
            </form>
        @endif
        @if ($quote->status->isEditable())
            <form method="POST" action="{{ route('quotes.destroy', $quote) }}" onsubmit="return confirm('Delete this quote?')">
                @csrf @method('DELETE')
                <x-button variant="danger">Delete</x-button>
            </form>
        @endif
    </x-page-header>

    <dl class="mb-6 grid max-w-xl grid-cols-3 gap-2 rounded-lg bg-white p-6 text-sm shadow">
        <dt class="text-slate-500">Status</dt><dd class="col-span-2"><x-status-badge :status="$quote->status" /></dd>
        <dt class="text-slate-500">Customer</dt><dd class="col-span-2"><a href="{{ route('customers.show', $quote->customer) }}" class="text-indigo-600 hover:underline">{{ $quote->customer->name }}</a></dd>
        <dt class="text-slate-500">Created by</dt><dd class="col-span-2">{{ $quote->user->name }}</dd>
        <dt class="text-slate-500">Valid until</dt><dd class="col-span-2">
            {{ $quote->valid_until?->format('Y-m-d') ?? '—' }}
            @if ($expired)
                <span class="ml-1 font-medium text-red-700">(expired{{ $quote->status->isEditable() ? ': change the date to send it' : '' }})</span>
            @endif
        </dd>
        <dt class="text-slate-500">Notes</dt><dd class="col-span-2">{{ $quote->notes }}</dd>
        @if ($quote->order)
            <dt class="text-slate-500">Order</dt><dd class="col-span-2"><a href="{{ route('orders.show', $quote->order) }}" class="text-indigo-600 hover:underline">{{ $quote->order->number }}</a></dd>
        @endif
    </dl>

    <div class="overflow-hidden rounded-lg bg-white shadow"><x-items-table :document="$quote" /></div>
</x-layout>

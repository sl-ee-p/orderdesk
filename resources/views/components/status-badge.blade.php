@props(['status'])
@php
    $colors = match ($status->value) {
        'draft' => 'bg-slate-100 text-slate-700',
        'sent', 'confirmed' => 'bg-blue-100 text-blue-800',
        'accepted', 'shipped' => 'bg-green-100 text-green-800',
        'rejected', 'cancelled' => 'bg-red-100 text-red-800',
    };
@endphp
<span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colors }}">{{ $status->label() }}</span>

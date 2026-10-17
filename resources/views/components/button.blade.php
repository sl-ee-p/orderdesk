@props(['href' => null, 'variant' => 'primary'])
@php
    $classes = 'inline-flex items-center rounded-md px-3 py-2 text-sm font-medium '.match ($variant) {
        'primary' => 'bg-indigo-600 text-white hover:bg-indigo-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-500',
        default => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
    };
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>{{ $slot }}</button>
@endif

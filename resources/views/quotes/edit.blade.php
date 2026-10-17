<x-layout title="Edit quote">
    <x-page-header title="Edit {{ $quote->number }}" />
    @include('quotes._form', ['action' => route('quotes.update', $quote), 'method' => 'PUT'])
</x-layout>

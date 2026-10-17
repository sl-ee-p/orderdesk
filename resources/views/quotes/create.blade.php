<x-layout title="New quote">
    <x-page-header title="New quote" />
    @include('quotes._form', ['action' => route('quotes.store')])
</x-layout>

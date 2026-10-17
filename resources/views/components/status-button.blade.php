@props(['action', 'status', 'variant' => 'primary'])
<form method="POST" action="{{ $action }}">
    @csrf
    <input type="hidden" name="status" value="{{ $status }}">
    <x-button :variant="$variant">{{ $slot }}</x-button>
</form>

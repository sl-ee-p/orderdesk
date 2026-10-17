<form method="POST" action="{{ $action }}" class="max-w-xl space-y-4 rounded-lg bg-white p-6 shadow">
    @csrf
    @isset($method) @method($method) @endisset
    <x-field name="name" label="Name" :value="$customer->name" required />
    <x-field name="email" label="Email" type="email" :value="$customer->email" required />
    <x-field name="phone" label="Phone" :value="$customer->phone" />
    <x-field name="company" label="Company" :value="$customer->company" />
    <x-field name="address" label="Address" :value="$customer->address" />
    <div class="flex gap-2">
        <x-button>Save</x-button>
        <x-button variant="secondary" :href="route('customers.index')">Cancel</x-button>
    </div>
</form>

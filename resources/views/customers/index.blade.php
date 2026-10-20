<x-layout title="Customers">
    <x-page-header title="Customers">
        <x-button :href="route('customers.create')">New customer</x-button>
    </x-page-header>

    <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr><th scope="col" class="px-4 py-2">Name</th><th scope="col" class="px-4 py-2">Company</th><th scope="col" class="px-4 py-2">Email</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="px-4 py-2"><a href="{{ route('customers.show', $customer) }}" class="text-indigo-600 hover:underline">{{ $customer->name }}</a></td>
                        <td class="px-4 py-2">{{ $customer->company }}</td>
                        <td class="px-4 py-2">{{ $customer->email }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">No customers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $customers->links() }}</div>
</x-layout>

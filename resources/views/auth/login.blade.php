<x-layout title="Log in">
    <div class="mx-auto max-w-sm rounded-lg bg-white p-6 shadow">
        <h1 class="mb-1 text-xl font-semibold">OrderDesk</h1>
        <p class="mb-6 text-sm text-slate-500">Demo login: demo@orderdesk.test / password</p>
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <x-field name="email" label="Email" type="email" required autofocus />
            <x-field name="password" label="Password" type="password" required />
            <x-button class="w-full justify-center">Log in</x-button>
        </form>
    </div>
</x-layout>

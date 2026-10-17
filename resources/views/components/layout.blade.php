<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}OrderDesk</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    @auth
        <nav class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center gap-6 px-4 py-3">
                <a href="{{ route('quotes.index') }}" class="text-lg font-semibold text-indigo-600">OrderDesk</a>
                @foreach (['quotes' => 'Quotes', 'orders' => 'Orders', 'customers' => 'Customers', 'products' => 'Products'] as $name => $label)
                    <a href="{{ route($name.'.index') }}"
                       class="text-sm {{ request()->routeIs($name.'.*') ? 'font-semibold text-indigo-600' : 'text-slate-600 hover:text-slate-900' }}">{{ $label }}</a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                    @csrf
                    <button class="text-sm text-slate-500 hover:text-slate-900">Log out ({{ auth()->user()->name }})</button>
                </form>
            </div>
        </nav>
    @endauth

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>

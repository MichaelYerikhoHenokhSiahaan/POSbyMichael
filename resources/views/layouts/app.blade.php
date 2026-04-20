<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Point Of Sale') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @php($manifestPath = public_path('build/manifest.json'))
        @php($manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [])
        @php($localHosts = ['localhost', '127.0.0.1', '::1'])
        @php($useHotFile = file_exists(public_path('hot')) && in_array(request()->getHost(), $localHosts, true))

        @if ($useHotFile || empty($manifest))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <link rel="stylesheet" href="/build/{{ $manifest['resources/css/app.css']['file'] }}">
            <script type="module" src="/build/{{ $manifest['resources/js/app.js']['file'] }}"></script>
        @endif
    </head>
    <body class="min-h-screen bg-slate-100 font-sans text-slate-900">
        @php($navigation = array_values(array_filter([
            
            auth()->user()?->isDeveloper() ? ['label' => 'Users', 'route' => 'users.index', 'active' => 'users.*'] : null,
            auth()->user()?->isDeveloper() ? ['label' => 'Products', 'route' => 'products.index', 'active' => 'products.*'] : null,
            auth()->user() ? ['label' => 'Restock', 'route' => 'restock.index', 'active' => 'restock.*'] : null,
            auth()->user()?->isDeveloper() ? ['label' => 'Warehouse', 'route' => 'warehouse.index', 'active' => 'warehouse.*'] : null,
            auth()->user() ? ['label' => 'Invoice Gudang', 'route' => 'warehouse-invoices.index', 'active' => 'warehouse-invoices.*'] : null,
            auth()->user() ? ['label' => 'Customers', 'route' => 'customers.index', 'active' => 'customers.*'] : null,
            auth()->user()?->isDeveloper() ? ['label' => 'Transactions', 'route' => 'transaction-records.index', 'active' => 'transaction-records.*'] : null,
            auth()->user()?->isDeveloper() ? ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard'] : null,
            auth()->user()?->isDeveloper() ? ['label' => 'Categories', 'route' => 'categories.index', 'active' => 'categories.*'] : null,
            ['label' => 'POS Sales', 'route' => 'sales.index', 'active' => 'sales.*'],
        ])))

        <div class="min-h-screen">
            <header class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-5 sm:px-6 lg:px-8">
                    <div class="auto-layout-header">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-[0.3em] text-emerald-600">Point Of Sale</p>
                            <h1 class="text-2xl font-semibold text-slate-900">Point of Sale Management</h1>
                            <p class="mt-1 text-sm text-slate-500">Manage products, customers, and transactions from one web dashboard.</p>
                        </div>
                        <div class="rounded-2xl bg-slate-900 px-5 py-3 text-sm text-white shadow-lg shadow-slate-300/40">
                            <p class="font-semibold">{{ now()->format('l, d M Y') }}</p>
                            <div class="mt-1 flex items-center gap-3 text-slate-300">
                                <p>{{ auth()->user()?->username ?? 'Cashier' }}</p>
                                <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-200">
                                    {{ auth()->user()?->role ?? 'guest' }}
                                </span>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white transition hover:bg-white/20">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <nav class="auto-layout-nav">
                        @foreach ($navigation as $item)
                            <a
                                href="{{ route($item['route']) }}"
                                class="auto-layout-nav-item rounded-full px-4 py-2 text-sm font-medium transition {{ request()->routeIs($item['active'] ?? $item['route']) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                            >
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </body>
</html>

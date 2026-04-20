<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Point Of Sale') }} Login</title>
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
        <div class="flex min-h-screen items-center justify-center px-4 py-6">
            <div class="flex w-full max-w-[560px] flex-col justify-center bg-white p-8 shadow-xl shadow-slate-300/40 ring-1 ring-slate-200 md:aspect-square md:max-w-[560px] md:rounded-none md:p-10">
                <div class="text-center">
                    <p class="text-sm font-medium uppercase tracking-[0.3em] text-emerald-600">Point Of Sale</p>
                    <h1 class="mt-3 text-3xl font-semibold text-slate-900">Login</h1>
                    <p class="mt-2 text-sm text-slate-500">Enter your credentials to access the POS dashboard.</p>
                </div>

                @if (session('status'))
                    <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('login.store') }}" method="POST" class="mt-8 space-y-5">
                    @csrf
                    <div>
                        <label for="username" class="text-sm font-medium text-slate-700">Username</label>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-700 focus:border-emerald-500 focus:outline-none" required autofocus>
                    </div>
                    <div>
                        <label for="password" class="text-sm font-medium text-slate-700">Password</label>
                        <input id="password" name="password" type="password" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                    </div>
                    <button type="submit" class="w-full rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-300/40">
                        Sign in
                    </button>
                </form>

            </div>
        </div>
    </body>
</html>

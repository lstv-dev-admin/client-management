<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Management</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-sm text-slate-800 antialiased">
    <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <form method="POST" action="{{ route('dry-run.toggle') }}">
                @csrf
                <button
                    type="submit"
                    class="rounded-md border px-2.5 py-1 text-xs font-medium {{ session('dry_run') ? 'border-amber-300 bg-amber-50 text-amber-900 hover:bg-amber-100' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    Dry run: {{ session('dry_run') ? 'On' : 'Off' }}
                </button>
            </form>
        </div>

        @if (session('dry_run'))
            <p class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">Dry run is on — actions show what would change, then nothing is saved.</p>
        @endif

        @if (session('status'))
            <x-flash tone="success">{{ session('status') }}</x-flash>
        @endif

        @include('partials.dry-run-report')

        @yield('content')
    </div>

    <div
        x-data
        x-cloak
        x-show="$store.confirm.open"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
        role="presentation"
        @click.self="$store.confirm.cancel()"
        @keydown.escape.window="$store.confirm.cancel()"
    >
        <div class="w-full max-w-sm rounded-lg border border-slate-200 bg-white p-4 shadow-lg" role="dialog" aria-modal="true" aria-labelledby="confirm-message">
            <p id="confirm-message" class="text-sm text-slate-800" x-text="$store.confirm.message"></p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50" @click="$store.confirm.cancel()">Cancel</button>
                <button
                    id="confirm-accept"
                    type="button"
                    class="rounded-md px-2.5 py-1 text-xs font-medium text-white"
                    :class="$store.confirm.danger ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-700 hover:bg-blue-800'"
                    @click="$store.confirm.accept()"
                    x-text="$store.confirm.danger ? 'Delete' : 'Save'"
                ></button>
            </div>
        </div>
    </div>
</body>
</html>

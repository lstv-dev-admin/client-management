@extends('layouts.app')

@section('content')
<div id="new-client" class="mb-5 scroll-mt-6" x-data="{ editing: {{ old('form') === 'client-create' ? 'true' : 'false' }} }">
    <div class="mb-4 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-lg font-semibold tracking-tight text-slate-900">Client Management</h1>
            <p class="text-xs text-slate-500">{{ $clients->total() }} {{ $clients->total() === 1 ? 'client' : 'clients' }}</p>
        </div>
        <button type="button" @click="editing = !editing" class="rounded-md bg-blue-700 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-blue-800">New client</button>
    </div>

    <div x-show="editing" @unless(old('form')==='client-create' ) x-cloak @endunless class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">New client</h2>
        @include('clients.partials.client-form', [
        'client' => null,
        'action' => route('clients.store'),
        'method' => 'POST',
        'submitLabel' => 'Save',
        ])
    </div>
</div>

<form
    method="GET"
    action="{{ route('clients.index') }}"
    class="mb-4 flex flex-wrap items-end gap-2"
    x-data
    @if ($search !=='' )
    x-init="$nextTick(() => { $refs.q.focus(); const end = $refs.q.value.length; $refs.q.setSelectionRange(end, end) })"
    @endif
    x-on:input.debounce.300ms="if ($event.target.name === 'q') $el.requestSubmit()">
    <label class="block min-w-56 flex-1">
        <span class="text-xs font-medium text-slate-500">Search</span>
        <input
            x-ref="q"
            type="search"
            name="q"
            value="{{ $search }}"
            placeholder="Name, code, address, product, or contact"
            class="mt-0.5 w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-800 outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700">
    </label>
    @if ($perPage !== 10)
        <input type="hidden" name="per" value="{{ $perPage }}">
    @endif
</form>

@if ($clients->isEmpty())
<p class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">
    @if ($search !== '')
    No clients match your search.
    @elseif ($clients->currentPage() > 1)
    No clients on this page.
    @else
    No clients yet. Add a company to get started.
    @endif
</p>
@else
<div class="space-y-4">
    @foreach ($clients as $client)
    @include('clients.partials.card', ['client' => $client])
    @endforeach
</div>

<div class="mt-4 space-y-2 text-xs text-slate-500">
    <div class="flex items-center justify-between gap-3">
        <p>Page {{ $clients->currentPage() }} of {{ $clients->lastPage() }}</p>
        <form method="GET" action="{{ route('clients.index') }}" class="flex items-center gap-2" x-data x-on:change="$el.requestSubmit()">
            @if ($search !== '')
                <input type="hidden" name="q" value="{{ $search }}">
            @endif
            <label class="flex items-center gap-2">
                <span class="font-medium text-slate-500">Per page</span>
                <select name="per" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-800 outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700">
                    @foreach ([10, 25, 50, 100] as $option)
                        <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    </div>
    @if ($clients->hasPages())
        {{ $clients->onEachSide(1)->links() }}
    @else
        <p>Showing {{ $clients->firstItem() }} to {{ $clients->lastItem() }} of {{ $clients->total() }}</p>
    @endif
</div>
@endif
@endsection
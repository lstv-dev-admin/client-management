@php
    $addForm = 'product-new-'.$client->recid;
    $adding = old('form') === $addForm;
@endphp

<section class="rounded-md bg-slate-50 p-3" x-data="{ adding: {{ $adding ? 'true' : 'false' }} }">
    <div class="mb-2 flex items-center justify-between gap-2">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Products</h3>
        <button type="button" @click="adding = true" x-show="!adding" @if($adding) x-cloak @endif class="text-xs font-medium text-blue-700 hover:text-blue-900">Add</button>
    </div>

    <ul class="divide-y divide-slate-200">
        @forelse ($client->products as $product)
            @php
                $formId = 'product-'.$product->recid;
                $isThisForm = old('form') === $formId;
            @endphp
            <li class="py-1.5" x-data="{ editing: {{ $isThisForm ? 'true' : 'false' }} }">
                <div x-show="!editing" @if($isThisForm) x-cloak @endif class="flex items-center justify-between gap-2">
                    <span class="min-w-0 break-words text-sm text-slate-800">{!! \App\Support\SearchHighlighter::highlight($product->prdname, $terms ?? []) !!}</span>
                    <span class="flex shrink-0 items-center gap-2">
                        <button type="button" @click="editing = true" class="text-xs font-medium text-blue-700 hover:text-blue-900">Edit</button>
                        <form method="POST" action="{{ route('clients.products.destroy', [$client, $product]) }}" x-on:submit="$store.confirm.ask($event, 'Delete this product?', true)">
                            @csrf
                            @method('DELETE')
                            @include('clients.partials.query-fields')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-800">Delete</button>
                        </form>
                    </span>
                </div>

                <form x-show="editing" @unless($isThisForm) x-cloak @endunless method="POST" action="{{ route('clients.products.update', [$client, $product]) }}" class="flex flex-wrap items-start gap-2" x-on:submit="$store.confirm.ask($event, 'Save changes to this product?')">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form" value="{{ $formId }}">
                    @include('clients.partials.query-fields')
                    <label class="min-w-40 flex-1">
                        <span class="text-xs font-medium text-slate-500">Product Name</span>
                        <input
                            name="prdname"
                            value="{{ $isThisForm ? old('prdname', $product->prdname) : $product->prdname }}"
                            required
                            maxlength="255"
                            class="mt-0.5 w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-800 outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
                        >
                        @error('prdname', $formId)
                            <span class="mt-0.5 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </label>
                    <span class="flex items-center gap-2 pt-4">
                        <button type="submit" class="rounded-md bg-blue-700 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-800">Save</button>
                        <button type="button" @click="editing = false" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50">Cancel</button>
                    </span>
                </form>
            </li>
        @empty
            <li x-show="!adding" @if($adding) x-cloak @endif class="py-1 text-xs text-slate-400">No products yet</li>
        @endforelse
    </ul>

    <form x-show="adding" @unless($adding) x-cloak @endunless method="POST" action="{{ route('clients.products.store', $client) }}" class="mt-2 flex flex-wrap items-start gap-2">
        @csrf
        <input type="hidden" name="form" value="{{ $addForm }}">
        @include('clients.partials.query-fields')
        <label class="min-w-40 flex-1">
            <span class="text-xs font-medium text-slate-500">Product Name</span>
            <input
                name="prdname"
                value="{{ $adding ? old('prdname') : '' }}"
                required
                maxlength="255"
                class="mt-0.5 w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-800 outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
            >
            @error('prdname', $addForm)
                <span class="mt-0.5 block text-xs text-red-600">{{ $message }}</span>
            @enderror
        </label>
        <span class="flex items-center gap-2 pt-4">
            <button type="submit" class="rounded-md bg-blue-700 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-800">Save</button>
            <button type="button" @click="adding = false" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50">Cancel</button>
        </span>
    </form>
</section>

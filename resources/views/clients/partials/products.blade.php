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
                <div x-show="!editing" @if($isThisForm) x-cloak @endif class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="break-words text-sm text-slate-800">{!! \App\Support\SearchHighlighter::highlight($product->prdname, $terms ?? []) !!}</p>
                        @if (filled($product->prdvers) || filled($product->prdnoli))
                            <p class="break-words text-xs text-slate-500">
                                {!! \App\Support\SearchHighlighter::highlight(collect([
                                    filled($product->prdvers) ? 'Version: '.$product->prdvers : null,
                                    filled($product->prdnoli) ? 'License: '.$product->prdnoli : null,
                                ])->filter()->implode(' · '), $terms ?? []) !!}
                            </p>
                        @endif
                    </div>
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

                <form x-show="editing" @unless($isThisForm) x-cloak @endunless method="POST" action="{{ route('clients.products.update', [$client, $product]) }}" class="grid grid-cols-1 gap-2 sm:grid-cols-2" x-on:submit="$store.confirm.ask($event, 'Save changes to this product?')">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form" value="{{ $formId }}">
                    @include('clients.partials.query-fields')
                    @foreach (App\Models\ClientProduct::detailFields() as $name => $label)
                        <label class="block {{ $name === 'prdname' ? 'sm:col-span-2' : '' }}">
                            <span class="text-xs font-medium text-slate-500">{{ $label }}</span>
                            <input
                                name="{{ $name }}"
                                value="{{ $isThisForm ? old($name, $product->$name) : ($product->$name ?? '') }}"
                                @if ($name === 'prdname') required @endif
                                maxlength="255"
                                class="mt-0.5 w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-800 outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
                            >
                            @error($name, $formId)
                                <span class="mt-0.5 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </label>
                    @endforeach
                    <span class="flex items-center gap-2 sm:col-span-2">
                        <button type="submit" class="rounded-md bg-blue-700 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-800">Save</button>
                        <button type="button" @click="editing = false" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50">Cancel</button>
                    </span>
                </form>
            </li>
        @empty
            <li x-show="!adding" @if($adding) x-cloak @endif class="py-1 text-xs text-slate-400">No products yet</li>
        @endforelse
    </ul>

    <form x-show="adding" @unless($adding) x-cloak @endunless method="POST" action="{{ route('clients.products.store', $client) }}" class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
        @csrf
        <input type="hidden" name="form" value="{{ $addForm }}">
        @include('clients.partials.query-fields')
        @foreach (App\Models\ClientProduct::detailFields() as $name => $label)
            <label class="block {{ $name === 'prdname' ? 'sm:col-span-2' : '' }}">
                <span class="text-xs font-medium text-slate-500">{{ $label }}</span>
                <input
                    name="{{ $name }}"
                    value="{{ $adding ? old($name) : '' }}"
                    @if ($name === 'prdname') required @endif
                    maxlength="255"
                    class="mt-0.5 w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-800 outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
                >
                @error($name, $addForm)
                    <span class="mt-0.5 block text-xs text-red-600">{{ $message }}</span>
                @enderror
            </label>
        @endforeach
        <span class="flex items-center gap-2 sm:col-span-2">
            <button type="submit" class="rounded-md bg-blue-700 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-800">Save</button>
            <button type="button" @click="adding = false" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50">Cancel</button>
        </span>
    </form>
</section>

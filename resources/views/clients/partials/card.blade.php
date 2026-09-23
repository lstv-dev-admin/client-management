@php
    $editing = old('form') === 'client-'.$client->recid;
    $mergeForm = 'merge-'.$client->recid;
    $mergeFailed = old('form') === $mergeForm;
    $askDelete = session('merge_delete') == $client->recid;
    $terms = $terms ?? [];
    $rbk = strcasecmp(trim((string) $client->bnkname), 'RBK') === 0;
    $highlight = fn (?string $text) => \App\Support\SearchHighlighter::highlight($text, $terms);
@endphp

<article
    id="client-{{ $client->recid }}"
    class="scroll-mt-6 rounded-lg border shadow-sm {{ $rbk ? 'border-amber-100 bg-[#fffdf8]' : 'border-slate-200 bg-white' }}"
    x-data="{ open: {{ $mergeFailed ? 'true' : 'false' }}, editing: {{ $editing ? 'true' : 'false' }} }"
    @if ($askDelete)
        x-init="$nextTick(() => $store.confirm.prompt($refs.deleteForm, 'Delete this company? Products and contacts you did not move will also be removed.', true))"
    @endif
>
    <div class="px-4 py-3" :class="open || editing ? 'border-b border-slate-100' : ''">
        <div x-show="!editing" @if($editing) x-cloak @endif>
            <div class="flex items-start justify-between gap-3" :class="open ? 'mb-3' : ''">
                <div class="flex min-w-0 items-start gap-2">
                    <button type="button" class="mt-0.5 shrink-0 rounded text-slate-500 hover:text-slate-800" @click="open = !open" :aria-expanded="open" aria-label="Show or hide company details">
                        <svg x-show="open" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                        </svg>
                        <svg x-show="!open" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M14.77 12.79a.75.75 0 0 1-1.06-.02L10 8.83 6.29 12.77a.75.75 0 1 1-1.08-1.04l4.25-4.5a.75.75 0 0 1 1.08 0l4.25 4.5a.75.75 0 0 1-.02 1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <h2 class="break-words text-sm font-semibold text-slate-900">{!! $highlight(filled($client->comname) ? $client->comname : $client->comcode) !!}</h2>
                        @if (filled($client->comname))
                            <p class="mt-0.5 text-xs text-slate-500">{!! $highlight($client->comcode) !!}</p>
                        @endif
                    </div>
                </div>
                <span class="flex shrink-0 items-center gap-2" x-show="open" x-cloak>
                    <span
                        class="inline-flex"
                        x-data="mergeDialog({{ \Illuminate\Support\Js::from([
                            'searchUrl' => route('clients.search', ['except' => $client->recid]),
                            'summaryUrl' => route('clients.summary', ['client' => 999999999]),
                            'products' => $client->products->map(fn ($product) => [
                                'recid' => $product->recid,
                                'prdname' => $product->prdname,
                                'prdnoli' => $product->prdnoli,
                                'prdvers' => $product->prdvers,
                            ])->values(),
                            'contacts' => $client->contacts->pluck('recid')->values(),
                            'reopen' => $mergeFailed,
                            'oldTarget' => $mergeFailed ? old('target') : null,
                            'oldProducts' => $mergeFailed ? array_values((array) old('products', [])) : [],
                            'oldContacts' => $mergeFailed ? array_values((array) old('contacts', [])) : [],
                            'oldCombineProducts' => $mergeFailed ? array_values((array) old('combine_products', [])) : [],
                        ]) }})"
                    >
                        <button type="button" @click="open = true" class="text-xs font-medium text-violet-700 hover:text-violet-900">Merge</button>
                        @include('clients.partials.merge-modal')
                    </span>
                    <button type="button" @click="editing = true" class="text-xs font-medium text-blue-700 hover:text-blue-900">Edit</button>
                    <form x-ref="deleteForm" method="POST" action="{{ route('clients.destroy', $client) }}" x-on:submit="$store.confirm.ask($event, 'Delete this client? Its products and contacts will also be removed.', true)">
                        @csrf
                        @method('DELETE')
                        @include('clients.partials.query-fields')
                        <button type="submit" class="text-xs text-red-600 hover:text-red-800">Delete</button>
                    </form>
                </span>
            </div>

            <dl x-show="open" x-cloak class="grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-3">
                @foreach (App\Models\Client::detailFields() as $name => $label)
                    <div class="{{ $name === 'comadd' ? 'col-span-2' : '' }}">
                        <dt class="text-xs text-slate-500">{{ $label }}</dt>
                        <dd class="break-words text-sm {{ filled($client->$name) ? 'text-slate-800' : 'text-slate-400' }}">{!! filled($client->$name) ? $highlight($client->$name) : '—' !!}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div x-show="editing" @unless($editing) x-cloak @endunless>
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Edit company details</h2>
            @include('clients.partials.client-form', [
                'client' => $client,
                'action' => route('clients.update', $client),
                'method' => 'PUT',
                'submitLabel' => 'Save',
            ])
        </div>
    </div>

    <div x-show="open" x-cloak class="grid gap-3 p-4 md:grid-cols-2">
        @include('clients.partials.products', ['client' => $client])
        @include('clients.partials.contacts', ['client' => $client])
    </div>
</article>

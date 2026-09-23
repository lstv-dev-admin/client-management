<div
    x-cloak
    x-show="open"
    class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4"
    role="presentation"
    @click.self="open = false"
    @keydown.escape.window="if (! $store.confirm.open) open = false"
>
    <form
        method="POST"
        action="{{ route('clients.merge', $client) }}"
        class="max-h-[85vh] w-full max-w-3xl overflow-y-auto rounded-lg border border-slate-200 bg-white p-4 shadow-lg"
        role="dialog"
        aria-modal="true"
        aria-labelledby="merge-title-{{ $client->recid }}"
        @submit="requestMerge($event)"
    >
        @csrf
        <input type="hidden" name="form" value="merge-{{ $client->recid }}">
        <input type="hidden" name="target" :value="target ? target.recid : ''">
        @include('clients.partials.query-fields')

        <div class="mb-3 flex items-start justify-between gap-3">
            <div>
                <h2 id="merge-title-{{ $client->recid }}" class="text-sm font-semibold text-slate-900">Merge products and contacts</h2>
                <p class="mt-0.5 text-xs text-slate-500">Move the ones you select from Company A to Company B. Company details stay unchanged.</p>
            </div>
            <button type="button" class="text-xs text-slate-500 hover:text-slate-800" @click="open = false">Close</button>
        </div>

        @error('target', 'merge-'.$client->recid)
            <p class="mb-2 text-xs text-red-600">{{ $message }}</p>
        @enderror
        @error('products', 'merge-'.$client->recid)
            <p class="mb-2 text-xs text-red-600">{{ $message }}</p>
        @enderror
        @error('contacts', 'merge-'.$client->recid)
            <p class="mb-2 text-xs text-red-600">{{ $message }}</p>
        @enderror
        @error('combine_products', 'merge-'.$client->recid)
            <p class="mb-2 text-xs text-red-600">{{ $message }}</p>
        @enderror

        <div x-show="!combining" class="grid gap-3 md:grid-cols-2">
            <section class="rounded-md bg-slate-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Company A</p>
                <h3 class="mt-1 break-words text-sm font-semibold text-slate-900">{{ filled($client->comname) ? $client->comname : $client->comcode }}</h3>
                @if (filled($client->comname))
                    <p class="text-xs text-slate-500">{{ $client->comcode }}</p>
                @endif

                <div class="mt-3">
                    <div class="mb-1 flex items-center justify-between gap-2">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Products</h4>
                        @if ($client->products->isNotEmpty())
                            <label class="flex items-center gap-1 text-xs text-slate-600">
                                <input type="checkbox" class="rounded border-slate-300" @change="productIds = $event.target.checked ? [...allProductIds] : []" :checked="allProductIds.length > 0 && productIds.length === allProductIds.length">
                                All
                            </label>
                        @endif
                    </div>
                    @forelse ($client->products as $product)
                        <label class="flex items-start gap-2 py-1 text-sm text-slate-800">
                            <input type="checkbox" name="products[]" value="{{ $product->recid }}" x-model="productIds" class="mt-0.5 rounded border-slate-300">
                            <span class="min-w-0">
                                <span class="block break-words">{{ $product->prdname }}</span>
                                @if (filled($product->prdvers) || filled($product->prdnoli))
                                    <span class="block break-words text-xs text-slate-500">{{ collect([filled($product->prdvers) ? 'Version: '.$product->prdvers : null, filled($product->prdnoli) ? 'License: '.$product->prdnoli : null])->filter()->implode(' · ') }}</span>
                                @endif
                            </span>
                        </label>
                    @empty
                        <p class="py-1 text-xs text-slate-400">No products yet</p>
                    @endforelse
                </div>

                <div class="mt-3">
                    <div class="mb-1 flex items-center justify-between gap-2">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Company Contacts</h4>
                        @if ($client->contacts->isNotEmpty())
                            <label class="flex items-center gap-1 text-xs text-slate-600">
                                <input type="checkbox" class="rounded border-slate-300" @change="contactIds = $event.target.checked ? [...allContactIds] : []" :checked="allContactIds.length > 0 && contactIds.length === allContactIds.length">
                                All
                            </label>
                        @endif
                    </div>
                    @forelse ($client->contacts as $contact)
                        <label class="flex items-start gap-2 py-1 text-sm text-slate-800">
                            <input type="checkbox" name="contacts[]" value="{{ $contact->recid }}" x-model="contactIds" class="mt-0.5 rounded border-slate-300">
                            <span class="min-w-0">
                                <span class="block break-words">{{ $contact->conperson }}</span>
                                @if (filled($contact->condesig))
                                    <span class="block break-words text-xs text-slate-500">{{ $contact->condesig }}</span>
                                @endif
                            </span>
                        </label>
                    @empty
                        <p class="py-1 text-xs text-slate-400">No contacts yet</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-md bg-slate-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Company B</p>

                <div x-show="!target" class="mt-2">
                    <label class="block">
                        <span class="text-xs font-medium text-slate-500">Search</span>
                        <input
                            type="search"
                            x-model="query"
                            @input.debounce.300ms="search"
                            @keydown.enter.prevent="search"
                            placeholder="Name, code, product, or contact"
                            class="mt-0.5 w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-800 outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
                        >
                    </label>
                    <p x-show="searching" class="mt-2 text-xs text-slate-400">Searching…</p>
                    <ul class="mt-2 divide-y divide-slate-200" x-show="results.length">
                        <template x-for="company in results" :key="company.recid">
                            <li>
                                <button type="button" class="w-full py-1.5 text-left hover:text-blue-800" @click="choose(company)">
                                    <span class="block break-words text-sm text-slate-800" x-html="highlight(company.comname || company.comcode)"></span>
                                    <span class="block text-xs text-slate-500" x-show="company.comname" x-html="highlight(company.comcode)"></span>
                                    <span class="block text-xs text-slate-500" x-show="company.hint" x-html="highlight(company.hint)"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <p class="mt-2 text-xs text-slate-400" x-show="!searching && query.trim() !== '' && results.length === 0">No companies match your search.</p>
                </div>

                <div x-show="target" x-cloak>
                    <div class="mt-1 flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="break-words text-sm font-semibold text-slate-900" x-text="target && (target.comname || target.comcode)"></h3>
                            <p class="text-xs text-slate-500" x-show="target && target.comname" x-text="target && target.comcode"></p>
                        </div>
                        <button type="button" class="shrink-0 text-xs font-medium text-blue-700 hover:text-blue-900" @click="clearTarget">Change</button>
                    </div>

                    <h4 class="mb-1 mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Products</h4>
                    <template x-if="target && target.products.length === 0">
                        <p class="py-1 text-xs text-slate-400">No products yet</p>
                    </template>
                    <ul class="divide-y divide-slate-200">
                        <template x-for="product in (target ? target.products : [])" :key="product.recid">
                            <li class="py-1">
                                <p class="break-words text-sm text-slate-800" x-text="product.prdname"></p>
                                <p class="break-words text-xs text-slate-500" x-show="product.prdvers || product.prdnoli" x-text="[product.prdvers ? 'Version: ' + product.prdvers : null, product.prdnoli ? 'License: ' + product.prdnoli : null].filter(Boolean).join(' · ')"></p>
                            </li>
                        </template>
                    </ul>

                    <h4 class="mb-1 mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Company Contacts</h4>
                    <template x-if="target && target.contacts.length === 0">
                        <p class="py-1 text-xs text-slate-400">No contacts yet</p>
                    </template>
                    <ul class="divide-y divide-slate-200">
                        <template x-for="contact in (target ? target.contacts : [])" :key="contact.recid">
                            <li class="py-1">
                                <p class="break-words text-sm text-slate-800" x-text="contact.conperson"></p>
                                <p class="break-words text-xs text-slate-500" x-show="contact.condesig" x-text="contact.condesig"></p>
                            </li>
                        </template>
                    </ul>
                </div>
            </section>
        </div>

        <div x-show="combining" x-cloak class="rounded-md border border-amber-100 bg-amber-50/60 p-3">
            <h3 class="text-sm font-semibold text-slate-900">These products already exist on Company B</h3>
            <p class="mt-0.5 text-xs text-slate-500">Choose which licenses to combine. Unchecked products stay on Company A so Company B does not get a second product with the same name.</p>

            <ul class="mt-3 divide-y divide-amber-100">
                <template x-for="row in collisions" :key="row.source.recid">
                    <li class="py-2">
                        <label class="flex items-start gap-2 text-sm text-slate-800">
                            <input type="checkbox" name="combine_products[]" :value="row.source.recid" x-model="combineIds" class="mt-0.5 rounded border-slate-300">
                            <span class="min-w-0">
                                <span class="block break-words font-medium" x-text="row.source.prdname"></span>
                                <span class="mt-0.5 block break-words text-xs text-slate-500">
                                    Company A:
                                    <span x-text="[row.source.prdvers ? 'Version: ' + row.source.prdvers : null, row.source.prdnoli ? 'License: ' + row.source.prdnoli : null].filter(Boolean).join(' · ') || '—'"></span>
                                </span>
                                <span class="block break-words text-xs text-slate-500">
                                    Company B:
                                    <span x-text="[row.target.prdvers ? 'Version: ' + row.target.prdvers : null, row.target.prdnoli ? 'License: ' + row.target.prdnoli : null].filter(Boolean).join(' · ') || '—'"></span>
                                </span>
                            </span>
                        </label>
                    </li>
                </template>
            </ul>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2" x-show="!combining">
            <button type="button" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50" @click="open = false">Cancel</button>
            <button type="submit" class="rounded-md bg-blue-700 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-slate-300" :disabled="!ready">Merge</button>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2" x-show="combining" x-cloak>
            <button type="button" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50" @click="cancelCombine">Back</button>
            <button type="button" class="rounded-md bg-blue-700 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-800" @click="confirmCombine($event)">Confirm merge</button>
        </div>
    </form>
</div>

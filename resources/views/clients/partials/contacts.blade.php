@php
    $addForm = 'contact-new-'.$client->recid;
    $adding = old('form') === $addForm;
    $inputClass = 'mt-0.5 w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-800 outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700';
@endphp

<section class="rounded-md bg-slate-50 p-3" x-data="{ adding: {{ $adding ? 'true' : 'false' }} }">
    <div class="mb-2 flex items-center justify-between gap-2">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Company Contacts</h3>
        <button type="button" @click="adding = true" x-show="!adding" @if($adding) x-cloak @endif class="text-xs font-medium text-blue-700 hover:text-blue-900">Add</button>
    </div>

    <ul class="divide-y divide-slate-200">
        @forelse ($client->contacts as $contact)
            @php
                $formId = 'contact-'.$contact->recid;
                $isThisForm = old('form') === $formId;
            @endphp
            <li class="py-2" x-data="{ editing: {{ $isThisForm ? 'true' : 'false' }} }">
                <div x-show="!editing" @if($isThisForm) x-cloak @endif class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="break-words text-sm font-medium text-slate-800">{!! \App\Support\SearchHighlighter::highlight($contact->conperson, $terms ?? []) !!}</p>
                        @if (filled($contact->condesig))
                            <p class="break-words text-xs text-slate-500">{!! \App\Support\SearchHighlighter::highlight($contact->condesig, $terms ?? []) !!}</p>
                        @endif
                        <p class="break-words text-xs text-slate-600">{!! \App\Support\SearchHighlighter::highlight(collect([$contact->contactnum, $contact->conemail])->filter(fn ($value) => filled($value))->implode(' · '), $terms ?? []) !!}</p>
                    </div>
                    <span class="flex shrink-0 items-center gap-2">
                        <button type="button" @click="editing = true" class="text-xs font-medium text-blue-700 hover:text-blue-900">Edit</button>
                        <form method="POST" action="{{ route('clients.contacts.destroy', [$client, $contact]) }}" x-on:submit="$store.confirm.ask($event, 'Delete this contact?', true)">
                            @csrf
                            @method('DELETE')
                            @include('clients.partials.query-fields')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-800">Delete</button>
                        </form>
                    </span>
                </div>

                <form x-show="editing" @unless($isThisForm) x-cloak @endunless method="POST" action="{{ route('clients.contacts.update', [$client, $contact]) }}" class="grid grid-cols-1 gap-2 sm:grid-cols-2" x-on:submit="$store.confirm.ask($event, 'Save changes to this contact?')">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form" value="{{ $formId }}">
                    @include('clients.partials.query-fields')
                    @foreach (App\Models\ClientContact::detailFields() as $name => $label)
                        <label class="block">
                            <span class="text-xs font-medium text-slate-500">{{ $label }}</span>
                            <input
                                name="{{ $name }}"
                                type="{{ $name === 'conemail' ? 'email' : ($name === 'contactnum' ? 'tel' : 'text') }}"
                                value="{{ $isThisForm ? old($name, $contact->$name) : $contact->$name }}"
                                required
                                maxlength="255"
                                class="{{ $inputClass }}"
                            >
                            @error($name, $formId)
                                <span class="mt-0.5 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </label>
                    @endforeach
                    <div class="flex items-center gap-2 sm:col-span-2">
                        <button type="submit" class="rounded-md bg-blue-700 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-800">Save</button>
                        <button type="button" @click="editing = false" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50">Cancel</button>
                    </div>
                </form>
            </li>
        @empty
            <li x-show="!adding" @if($adding) x-cloak @endif class="py-1 text-xs text-slate-400">No contacts yet</li>
        @endforelse
    </ul>

    <form x-show="adding" @unless($adding) x-cloak @endunless method="POST" action="{{ route('clients.contacts.store', $client) }}" class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
        @csrf
        <input type="hidden" name="form" value="{{ $addForm }}">
        @include('clients.partials.query-fields')
        @foreach (App\Models\ClientContact::detailFields() as $name => $label)
            <label class="block">
                <span class="text-xs font-medium text-slate-500">{{ $label }}</span>
                <input
                    name="{{ $name }}"
                    type="{{ $name === 'conemail' ? 'email' : ($name === 'contactnum' ? 'tel' : 'text') }}"
                    value="{{ $adding ? old($name) : '' }}"
                    required
                    maxlength="255"
                    class="{{ $inputClass }}"
                >
                @error($name, $addForm)
                    <span class="mt-0.5 block text-xs text-red-600">{{ $message }}</span>
                @enderror
            </label>
        @endforeach
        <div class="flex items-center gap-2 sm:col-span-2">
            <button type="submit" class="rounded-md bg-blue-700 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-800">Save</button>
            <button type="button" @click="adding = false" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50">Cancel</button>
        </div>
    </form>
</section>

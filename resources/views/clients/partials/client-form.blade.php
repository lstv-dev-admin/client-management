@php
    $formId = $client ? 'client-'.$client->recid : 'client-create';
    $isThisForm = old('form') === $formId;
@endphp

<form method="POST" action="{{ $action }}" class="grid grid-cols-1 gap-2 sm:grid-cols-2" @if(($method ?? 'POST') !== 'POST') x-on:submit="$store.confirm.ask($event, 'Save changes to this company?')" @endif>
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif
    <input type="hidden" name="form" value="{{ $formId }}">
    @include('clients.partials.query-fields')

    @foreach (App\Models\Client::detailFields() as $name => $label)
        @if ($name === 'comcode' && $client === null)
            @continue
        @endif
        <label class="block {{ $name === 'comadd' && $client !== null ? 'sm:col-span-2' : '' }}">
            <span class="text-xs font-medium text-slate-500">{{ $label }}</span>
            <input
                name="{{ $name }}"
                value="{{ $isThisForm ? old($name, $client?->$name) : ($client?->$name ?? '') }}"
                required
                maxlength="255"
                class="mt-0.5 w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-800 outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
            >
            @error($name, $formId)
                <span class="mt-0.5 block text-xs text-red-600">{{ $message }}</span>
            @enderror
        </label>
    @endforeach

    <div class="flex items-center gap-2 sm:col-span-2">
        <button type="submit" class="rounded-md bg-blue-700 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-800">{{ $submitLabel }}</button>
        <button type="button" @click="editing = false" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50">Cancel</button>
    </div>
</form>

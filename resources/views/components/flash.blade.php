@props([
    'tone' => 'success',
    'seconds' => 5,
])

@php
    $tones = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-950',
    ];
    $classes = $tones[$tone] ?? $tones['success'];
@endphp

<div
    x-data="{ show: true }"
    x-init="setTimeout(() => show = false, {{ (int) $seconds * 1000 }})"
    x-show="show"
    x-transition.opacity
    {{ $attributes->class(['mb-4 rounded-md border px-3 py-2 text-sm', $classes]) }}
    role="status"
>
    <div class="flex items-start gap-3">
        <div class="min-w-0 flex-1">
            {{ $slot }}
        </div>
        <button
            type="button"
            class="shrink-0 rounded px-1 text-base leading-none opacity-60 hover:opacity-100"
            aria-label="Dismiss"
            @click="show = false"
        >&times;</button>
    </div>
</div>

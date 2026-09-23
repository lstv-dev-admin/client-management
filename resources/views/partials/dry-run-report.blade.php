@if ($report = session('dry_run_report'))
    <x-flash tone="amber" :seconds="8" class="rounded-lg px-4 py-3">
        <p class="font-semibold text-amber-950">Dry run result — not saved</p>
        <p class="mt-0.5 text-xs text-amber-900/80">{{ $report['title'] ?? 'Action preview' }}</p>

        @foreach (($report['sections'] ?? []) as $section)
            <div class="mt-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-900/70">{{ $section['heading'] }}</p>
                <ul class="mt-1 space-y-2">
                    @foreach (($section['items'] ?? []) as $item)
                        <li class="rounded-md border border-amber-100 bg-white/80 px-3 py-2">
                            <p class="font-medium text-slate-900">{{ $item['label'] }}</p>
                            @if (! empty($item['before']))
                                <p class="mt-1 break-words text-xs text-slate-500">Before: {{ is_array($item['before']) ? collect($item['before'])->map(fn ($value, $key) => $key.': '.(filled($value) ? $value : '—'))->implode(' · ') : $item['before'] }}</p>
                            @endif
                            @if (! empty($item['after']))
                                <p class="mt-0.5 break-words text-xs text-slate-700">After: {{ is_array($item['after']) ? collect($item['after'])->map(fn ($value, $key) => $key.': '.(filled($value) ? $value : '—'))->implode(' · ') : $item['after'] }}</p>
                            @endif
                            @if (! empty($item['note']))
                                <p class="mt-0.5 text-xs text-slate-500">{{ $item['note'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </x-flash>
@endif

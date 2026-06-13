@props(['paginator'])

@php
    $displayedCount = $paginator && method_exists($paginator, 'count') ? (int) $paginator->count() : 0;
    $totalCount = $paginator && method_exists($paginator, 'total') ? (int) $paginator->total() : $displayedCount;
    $recordLabel = $totalCount === 1 ? 'record' : 'records';
@endphp

@if($displayedCount > 0)
    <div {{ $attributes->class(['border-t border-emerald-950/10 px-6 py-4']) }}>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <p class="text-sm font-semibold text-slate-600">
                Displaying
                <span class="text-slate-950">{{ number_format($displayedCount) }}</span>
                out of
                <span class="text-slate-950">{{ number_format($totalCount) }}</span>
                {{ $recordLabel }}.
            </p>

            @if($paginator->hasPages())
                <div class="w-full lg:w-auto">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </div>
@endif

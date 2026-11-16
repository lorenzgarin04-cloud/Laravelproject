@extends('layouts.app')
@section('title', 'Analytics')
@section('crumb-parent', 'Overview')
@section('crumb-parent-url', route('dashboard'))
@section('crumb', 'Reports & Analytics')

@section('content')
@php
    $colors = ['branded' => '#00945c', 'generic' => '#2b72d6', 'grocery' => '#ee6c35'];
    $types = \App\Models\Product::TYPES;

    $deltaText = function (array $d) use ($compareText) {
        return match ($d['kind']) {
            'new'  => '▲ new',
            'none' => '—',
            default => ($d['up'] ? '▲ +' : '▼ ') . $d['pct'] . '%',
        };
    };
@endphp
<div class="mx-auto min-w-0 max-w-[1100px]">
    <span class="pill-green font-mono text-[11px] uppercase tracking-wider">Financial Summary</span>
    <h1 class="mt-2 font-display text-[32px] font-bold leading-tight text-ink">Reports</h1>
    <p class="mt-1 text-sm text-body">How Branded, Generic and Grocery are selling — {{ $rangeLabel }}.</p>

    <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
        @include('reports._tabs', ['active' => 'analytics'])
        <div class="mt-5 inline-flex rounded-xl border border-mint-2 bg-white p-1 shadow-sm">
            @foreach ($periods as $k => $label)
                <a href="{{ route('reports.analytics', ['period' => $k]) }}" @if ($period === $k) aria-current="true" @endif
                   class="rounded-lg px-3.5 py-2 text-sm font-semibold transition {{ $period === $k ? 'bg-brand text-white' : 'text-ink hover:bg-mint' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    {{-- Totals --}}
    <div class="mt-5 grid gap-4 md:grid-cols-[1.2fr_1fr_1fr_1fr]">
        <div class="card p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Total Sales</p>
            <p class="mt-1 font-display text-[30px] font-bold leading-tight text-brand-deep">₱{{ number_format($now['net']['all'], 2) }}</p>
            <p class="mt-1 text-xs text-muted">
                <span class="{{ $deltas['all']['kind'] === 'pct' && ! $deltas['all']['up'] ? 'text-danger' : 'text-brand-dark' }} font-semibold">{{ $deltaText($deltas['all']) }}</span>
                vs {{ $compareText }} • {{ $now['orders'] }} {{ $now['orders'] === 1 ? 'order' : 'orders' }}
            </p>
        </div>
        @foreach ($types as $key => $label)
            <div class="card p-5">
                <p class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-muted"><span class="h-2.5 w-2.5 rounded-sm" style="background: {{ $colors[$key] }}"></span> {{ $label }}</p>
                <p class="mt-1 font-display text-2xl font-bold leading-tight text-ink">₱{{ number_format($now['net'][$key], 2) }}</p>
                <p class="mt-1 text-xs text-muted">
                    {{ rtrim(rtrim(number_format($share[$key]['percent'], 1), '0'), '.') }}% of sales • {{ $now['qty'][$key] }} sold •
                    <span class="{{ $deltas[$key]['kind'] === 'pct' && ! $deltas[$key]['up'] ? 'text-danger' : 'text-brand-dark' }} font-semibold">{{ $deltaText($deltas[$key]) }}</span>
                </p>
            </div>
        @endforeach
    </div>

    {{-- Sales over time --}}
    <section class="card mt-5 p-6" x-data="analyticsChart()">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-display text-lg font-bold text-ink">Sales over time</h2>
                <p class="text-xs text-muted">{{ $period === 'today' ? 'Per hour, today' : ($period === 'year' ? 'Per month, this year' : 'Per day') }} • net of Senior/PWD discount</p>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-ink">
                @foreach ($types as $key => $label)
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm" style="background: {{ $colors[$key] }}"></span> {{ $label }}</span>
                @endforeach
                <button type="button" @click="table = !table" class="btn-outline btn-sm" x-text="table ? 'Show chart' : 'Show table'"></button>
            </div>
        </div>

        @unless ($hasSales)
            <p class="mt-8 rounded-lg border border-mint-2 bg-mint-3 py-14 text-center text-sm text-muted">No sales in this period yet. Record a sale in POS and it will show up here.</p>
        @else
            {{-- Chart --}}
            <div x-show="!table" class="mt-6">
                <div class="flex">
                    <div class="relative mr-2 h-[260px] w-12 shrink-0 text-right text-[11px] text-muted">
                        <template x-for="t in ticks" :key="t">
                            <span class="absolute right-0 -translate-y-1/2" :style="'bottom:' + (t / top * 100) + '%'" x-text="'₱' + t.toLocaleString('en-PH')"></span>
                        </template>
                    </div>
                    <div class="relative h-[260px] min-w-0 flex-1 border-b border-line">
                        <template x-for="t in ticks" :key="'g' + t">
                            <div class="absolute inset-x-0 border-t border-mint-2" :style="'bottom:' + (t / top * 100) + '%'"></div>
                        </template>
                        <div class="absolute inset-0 flex items-end justify-around gap-1 px-1">
                            <template x-for="(b, i) in series" :key="b.key">
                                <div class="relative flex h-full min-w-0 flex-1 items-end justify-center" @mouseenter="hover = i" @mouseleave="hover = null" @focusin="hover = i" @focusout="hover = null" tabindex="0">
                                    <div class="flex w-full max-w-[44px] flex-col-reverse overflow-hidden rounded-t" :style="'height:' + (b.total / top * 100) + '%'">
                                        <template x-for="t in Object.keys(types)" :key="t">
                                            <div :style="'background:' + colors[t] + ';height:' + (b.total > 0 ? b[t] / b.total * 100 : 0) + '%'"></div>
                                        </template>
                                    </div>
                                    <div x-show="hover === i" x-cloak class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 w-44 -translate-x-1/2 rounded-lg border border-mint-2 bg-white p-3 text-xs shadow-lg">
                                        <p class="mb-1.5 font-bold text-ink" x-text="b.label"></p>
                                        <template x-for="t in Object.keys(types)" :key="'tt' + t">
                                            <p class="flex items-center justify-between gap-2 py-0.5">
                                                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm" :style="'background:' + colors[t]"></span><span x-text="types[t]"></span></span>
                                                <span class="font-semibold" x-text="peso(b[t])"></span>
                                            </p>
                                        </template>
                                        <p class="mt-1 flex justify-between border-t border-mint-2 pt-1.5 font-bold text-ink"><span>Total</span><span x-text="peso(b.total)"></span></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="ml-14 mt-2 flex justify-around gap-1 text-[11px] text-muted">
                    <template x-for="b in series" :key="'l' + b.key"><span class="min-w-0 flex-1 truncate text-center" x-text="b.label"></span></template>
                </div>
            </div>

            {{-- Table view --}}
            <div x-show="table" x-cloak class="mt-6 overflow-x-auto">
                <table class="w-full min-w-[520px] text-sm">
                    <thead class="bg-mint-3"><tr><th class="th">When</th>@foreach ($types as $label)<th class="th text-right">{{ $label }}</th>@endforeach<th class="th text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-mint-2">
                        <template x-for="b in series" :key="'r' + b.key">
                            <tr>
                                <td class="px-4 py-2.5 font-semibold text-ink" x-text="b.label"></td>
                                <template x-for="t in Object.keys(types)" :key="'c' + t"><td class="px-4 py-2.5 text-right text-body" x-text="peso(b[t])"></td></template>
                                <td class="px-4 py-2.5 text-right font-bold text-brand-dark" x-text="peso(b.total)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        @endunless
    </section>

    {{-- Share + Top 5 --}}
    <div class="mt-5 grid items-start gap-5 lg:grid-cols-2">
        <section class="card min-w-0 p-6">
            <h2 class="font-display text-lg font-bold text-ink">Share of sales</h2>
            <p class="text-xs text-muted">Which type brings in the most money</p>

            @if ($now['net']['all'] > 0)
                <div class="mt-4 flex h-11 overflow-hidden rounded-lg bg-mint-2" role="img" aria-label="Share of sales by type">
                    @foreach ($share as $key => $row)
                        @if ($row['percent'] > 0)
                            <div class="grid place-items-center text-sm font-bold text-white" style="width: {{ $row['percent'] }}%; background: {{ $colors[$key] }}">{{ $row['percent'] >= 8 ? round($row['percent']) . '%' : '' }}</div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="mt-4 h-11 rounded-lg bg-mint-2"></div>
            @endif

            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($share as $key => $row)
                    <li class="flex items-center justify-between gap-3">
                        <span class="flex items-center gap-2 text-ink"><span class="h-2.5 w-2.5 rounded-sm" style="background: {{ $colors[$key] }}"></span> {{ $row['label'] }}</span>
                        <span class="font-semibold text-ink">₱{{ number_format($row['amount'], 2) }} • {{ rtrim(rtrim(number_format($row['percent'], 1), '0'), '.') ?: '0' }}%</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="card min-w-0 p-6">
            <h2 class="font-display text-lg font-bold text-ink">Top 5 best sellers</h2>
            <p class="text-xs text-muted">By sales amount, {{ strtolower($periods[$period]) }}</p>

            @forelse ($top5 as $i => $item)
                <div class="mt-4">
                    <div class="flex items-baseline justify-between gap-3 text-sm">
                        <p class="min-w-0 break-words text-ink [overflow-wrap:anywhere]"><span class="font-semibold">{{ $i + 1 }}. {{ $item['name'] }}</span>
                            <span class="text-[11px] text-muted">{{ $types[$item['type']] ?? ucfirst($item['type']) }} • {{ $item['qty'] }} sold</span></p>
                        <p class="shrink-0 font-display font-bold text-ink">₱{{ number_format($item['net'], 2) }}</p>
                    </div>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-mint-2"><div class="h-full rounded-full bg-brand" style="width: {{ $top5[0]['net'] > 0 ? round($item['net'] / $top5[0]['net'] * 100) : 0 }}%"></div></div>
                </div>
            @empty
                <p class="mt-6 rounded-lg border border-mint-2 bg-mint-3 py-10 text-center text-sm text-muted">No sales in this period yet.</p>
            @endforelse
        </section>
    </div>
</div>

@push('scripts')
<script>
window.ANALYTICS = @json($chart);
window.ANALYTICS_COLORS = @json($colors);

function analyticsChart() {
    const data = window.ANALYTICS;
    return {
        series: data.series, ticks: data.ticks, top: data.top || 1, types: data.types, colors: window.ANALYTICS_COLORS,
        hover: null, table: false,
        peso(n) { return window.peso(n); },
    };
}
</script>
@endpush
@endsection

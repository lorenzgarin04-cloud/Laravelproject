@extends('layouts.app')
@section('title', 'Daily Report')
@section('crumb-parent', 'Reports')
@section('crumb-parent-url', route('reports.index'))
@section('crumb', 'Daily Report')

@section('content')
@php
    $prev = $date->copy()->subDay();
    $tints = [
        'branded' => 'border-brand/30 bg-mint-2 text-brand-dark',
        'generic' => 'border-[#b9d3f5] bg-[#eaf2fe] text-[#1f5fbf]',
        'grocery' => 'border-warn-line bg-[#fff1e8] text-[#c2481b]',
    ];
    $dots = ['branded' => '#00945c', 'generic' => '#2b72d6', 'grocery' => '#ee6c35'];

    // Bilang para sa legend (batay sa kasalukuyang ipinapakitang mga talahanayan)
    $soldRows = 0; $alertRows = 0; $expiredRows = 0;
    foreach ($daily['columns'] as $col) {
        foreach ($col['rows'] as $r) {
            if ($r['qty'] > 0) $soldRows++;
            if ($r['state'] === 'alert') $alertRows++;
            if ($r['expired']) $expiredRows++;
        }
    }
    $filters = ['all' => 'All'] + \App\Models\Product::TYPES;
    $exportQuery = array_filter(['type' => 'daily', 'date' => $date->toDateString(), 'category' => $category !== 'all' ? $category : null]);
@endphp

<div class="mx-auto min-w-0 max-w-[1100px]">
    {{-- Heading --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <span class="pill-green font-mono text-[11px] uppercase tracking-wider">Reports</span>
            <h1 class="mt-2 font-display text-[32px] font-bold leading-tight text-ink">Daily Report</h1>
            <p class="mt-1 max-w-xl text-sm text-body">Branded, Generic and Grocery — what was sold and how much stock is left. Saved automatically every day.</p>
        </div>
        <div class="no-print flex gap-3">
            <button type="button" onclick="window.print()" class="btn-outline"><x-icon name="printer" class="h-4 w-4" /> Print</button>
            <a href="{{ route('reports.export', $exportQuery) }}" class="btn-primary shadow-md"><x-icon name="download" class="h-4 w-4" /> Export</a>
        </div>
    </div>

    @include('reports._tabs', ['active' => 'daily'])

    {{-- Date controls --}}
    <form method="GET" action="{{ route('reports.index') }}" class="card mt-5 flex flex-wrap items-center justify-between gap-3 px-4 py-3">
        <input type="hidden" name="type" value="daily">
        @if ($category !== 'all') <input type="hidden" name="category" value="{{ $category }}"> @endif
        <div class="no-print flex flex-wrap items-center gap-2">
            <a href="{{ route('reports.index', array_filter(['type' => 'daily', 'category' => $category !== 'all' ? $category : null, 'date' => $prev->toDateString()])) }}" class="btn-outline btn-sm !px-3 !py-2.5" aria-label="Previous day">‹ {{ $prev->format('M j') }}</a>
            <input id="date" name="date" type="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()" class="input w-auto min-w-0 py-2 text-sm" aria-label="Report date">
            @if ($date->lt(today()))
                <a href="{{ route('reports.index', array_filter(['type' => 'daily', 'category' => $category !== 'all' ? $category : null, 'date' => $date->copy()->addDay()->toDateString()])) }}" class="btn-outline btn-sm !px-3 !py-2.5" aria-label="Next day">›</a>
            @else
                <span class="btn-outline btn-sm cursor-not-allowed !px-3 !py-2.5 opacity-50" aria-disabled="true">›</span>
            @endif
        </div>
        <p class="hidden text-sm font-semibold text-ink print:block">{{ $date->format('l, F j, Y') }}</p>
        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
            <span class="pill-green"><span class="h-1.5 w-1.5 rounded-full bg-brand"></span> Live — updates with every sale</span>
            <span class="inline-flex items-center rounded-full border border-line bg-white px-3 py-1 text-ink">{{ $totals['sales'] }} {{ $totals['sales'] === 1 ? 'sale' : 'sales' }} • ₱{{ number_format($totals['grand'], 2) }}</span>
        </div>
    </form>

    {{-- Type cards --}}
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($totals['columns'] as $key => $col)
            <div class="card p-4">
                <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $tints[$key] }}">{{ $col['label'] }}</span>
                <p class="mt-3 font-display text-[28px] font-bold leading-none text-ink">₱{{ number_format($col['total'], 2) }}</p>
                <p class="mt-1.5 text-xs text-muted">{{ number_format($col['qty']) }} {{ $col['qty'] === 1 ? 'item' : 'items' }} sold</p>
            </div>
        @endforeach
        <div class="card border-brand/40 p-4 ring-1 ring-brand/10">
            <span class="inline-flex rounded-full border border-brand/30 bg-mint-2 px-2.5 py-0.5 text-[11px] font-semibold text-brand-dark">Total</span>
            <p class="mt-3 font-display text-[28px] font-bold leading-none text-brand">₱{{ number_format($totals['grand'], 2) }}</p>
            <p class="mt-1.5 text-xs text-muted">{{ $totals['sales'] }} {{ $totals['sales'] === 1 ? 'sale' : 'sales' }}</p>
        </div>
    </div>

    {{-- Stock --}}
    <section class="card mt-5 p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-display text-lg font-bold text-ink">Stock — {{ $date->format('F j, Y') }}</h2>
                <p class="mt-0.5 text-xs text-muted">“Last stock” is what was left at the end of {{ $prev->format('M j') }}. Every sale lowers “Stock left”.</p>
            </div>
            <div class="no-print inline-flex rounded-xl border border-mint-2 bg-mint-3 p-1">
                @foreach ($filters as $k => $label)
                    <a href="{{ route('reports.index', array_filter(['type' => 'daily', 'date' => $date->toDateString(), 'category' => $k !== 'all' ? $k : null])) }}"
                       class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $category === $k ? 'bg-white text-ink shadow-sm' : 'text-body hover:text-ink' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold">
            <span class="pill-green"><span class="h-1.5 w-1.5 rounded-full bg-brand"></span> Sold ({{ $soldRows }})</span>
            <span class="pill-warn"><span class="h-1.5 w-1.5 rounded-full bg-warn"></span> Low / Expiring ({{ $alertRows }})</span>
            <span class="pill-red"><span class="h-1.5 w-1.5 rounded-full bg-danger"></span> Expired ({{ $expiredRows }})</span>
        </div>
    </section>

    @foreach ($daily['columns'] as $key => $col)
        <section class="card mt-5 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $tints[$key] }}">{{ $col['label'] }}</span>
                    <span class="text-sm text-body">{{ $col['items'] }} {{ $col['items'] === 1 ? 'product' : 'products' }}</span>
                </div>
                <span class="text-sm text-body">{{ $col['sold'] }} sold • <b class="text-ink">₱{{ number_format($col['total'], 2) }}</b></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead class="bg-mint-3">
                        <tr>
                            <th class="th">Product</th>
                            <th class="th text-right">Price</th>
                            <th class="th">Expiration</th>
                            <th class="th text-center">Last stock<span class="block font-medium normal-case tracking-normal text-muted">{{ $prev->format('M j') }}</span></th>
                            <th class="th text-center">Sold</th>
                            <th class="th text-center">Added / Removed</th>
                            <th class="th text-right">Stock left<span class="block font-medium normal-case tracking-normal text-muted">{{ $date->format('M j') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($col['rows'] as $r)
                            @php
                                $rowClass = match ($r['state']) {
                                    'sold'    => 'border-l-brand bg-mint-2/60',
                                    'alert'   => 'border-l-warn bg-warn-bg',
                                    'expired' => 'border-l-danger bg-danger-bg',
                                    default   => 'border-l-transparent bg-white',
                                };
                            @endphp
                            <tr class="border-l-4 border-t border-t-mint-2 align-middle {{ $rowClass }}">
                                <td class="px-4 py-3">
                                    <p class="flex flex-wrap items-center gap-2 break-words font-semibold leading-snug text-ink [overflow-wrap:anywhere]">
                                        {{ $r['name'] }}
                                        @if ($r['rx']) <span class="rounded border border-warn-line bg-warn-bg px-1.5 text-[10px] font-bold text-warn">Rx</span> @endif
                                    </p>
                                    <p class="text-[11px] text-muted">per {{ $r['unit'] }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-body">₱{{ number_format($r['price'], 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="text-body">{{ $r['expires']->format('M d, Y') }}</span>
                                    @if ($r['expired'])
                                        <span class="block text-[11px] font-semibold text-danger">Expired</span>
                                    @elseif ($r['expiring'])
                                        <span class="block text-[11px] font-semibold text-warn">{{ $r['days_left'] <= 0 ? 'Expires today' : $r['days_left'] . ($r['days_left'] === 1 ? ' day left' : ' days left') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-body">{{ number_format($r['last']) }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($r['qty'] > 0)
                                        <span class="font-bold text-ink">{{ number_format($r['qty']) }}</span>
                                        <span class="block text-[11px] font-semibold text-brand-dark">₱{{ number_format($r['net'], 2) }}</span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($r['added'] > 0) <span class="font-bold text-brand-dark">+{{ number_format($r['added']) }}</span>
                                    @elseif ($r['added'] < 0) <span class="font-bold text-danger">−{{ number_format(abs($r['added'])) }}</span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-display text-lg font-bold {{ $r['stock'] === 0 ? 'text-danger' : ($r['low'] ? 'text-warn' : 'text-ink') }}">{{ number_format($r['stock']) }}</span>
                                    @if ($r['stock'] === 0) <span class="block text-[11px] font-semibold text-danger">Out of stock</span>
                                    @elseif ($r['low']) <span class="block text-[11px] font-semibold text-warn">Low stock</span> @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="border-t border-mint-2 px-5 py-10 text-center text-sm text-muted">No {{ strtolower($col['label']) }} products on this day.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach

    <p class="no-print mt-5 flex flex-wrap items-center justify-between gap-3 text-xs text-muted">
        <span>Stock figures are saved automatically with every sale and every stock change (+/− or edit). Sold amounts are net of Senior/PWD discount.</span>
        <a href="{{ route('reports.index', ['type' => 'detailed']) }}" class="font-semibold text-brand-dark hover:underline">View the detailed sales list →</a>
    </p>
</div>
@endsection

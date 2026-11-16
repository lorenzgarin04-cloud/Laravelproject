@extends('layouts.app')
@section('title', 'Reports')
@section('crumb-parent', 'Overview')
@section('crumb-parent-url', route('dashboard'))
@section('crumb', 'Reports & Analytics')

@section('content')
@php
    $exportQuery = array_filter([
        'type' => $type, 'category' => $category,
        'date' => $date->toDateString(), 'from' => $from->toDateString(), 'to' => $to->toDateString(),
    ]);
    $quarterDate = $type === 'daily' ? $date : $to;
@endphp
<div class="mx-auto min-w-0 max-w-[1100px]">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs">
                <span class="pill-green font-mono uppercase tracking-wider">Financial Summary</span>
                <span class="text-muted">• Q{{ $quarterDate->quarter }} {{ $quarterDate->year }}</span>
            </div>
            <h1 class="mt-2 font-display text-[32px] font-bold leading-tight text-ink">Reports</h1>
            <p class="mt-1 text-sm text-body">Track sales trends, inventory turnover, and dispense history across dates.</p>
        </div>
        <a href="{{ route('reports.export', $exportQuery) }}" class="btn-primary px-6 py-3.5 shadow-md">
            <x-icon name="download" class="h-4 w-4" /> Export This Report
        </a>
    </div>

    @include('reports._tabs', ['active' => 'stock'])

    {{-- Parameters --}}
    <form method="GET" action="{{ route('reports.index') }}" class="card mt-5 p-6">
        <div class="flex items-center justify-between border-b border-mint-2 pb-3">
            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-ink"><x-icon name="sliders" class="h-4 w-4 text-brand-dark" /> Report Parameters</h2>
            <span class="hidden text-xs text-muted sm:block">Showing live pharmacy dispensary log</span>
        </div>
        <div class="mt-4 grid gap-5 md:grid-cols-[1.1fr_1fr_1.4fr]">
            <div>
                <label for="type" class="mb-1.5 block text-xs font-bold text-ink">Choose Report <span class="text-brand">*</span></label>
                <div class="relative">
                    <select id="type" name="type" onchange="this.form.submit()" class="input appearance-none py-2.5 pr-10 text-sm">
                        @foreach ($types as $k => $label) <option value="{{ $k }}" @selected($type === $k)>{{ $label }}</option> @endforeach
                    </select>
                    <x-icon name="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                </div>
            </div>
            <div>
                <label for="category" class="mb-1.5 block text-xs font-bold text-ink">Category</label>
                <div class="relative">
                    <select id="category" name="category" onchange="this.form.submit()" class="input appearance-none py-2.5 pr-10 text-sm">
                        @foreach ($categories as $k => $label) <option value="{{ $k }}" @selected($category === $k)>{{ $label }}</option> @endforeach
                    </select>
                    <x-icon name="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                </div>
            </div>

            @if ($type === 'daily')
                <div>
                    <label for="date" class="mb-1.5 block text-xs font-bold text-ink">Date</label>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('reports.index', ['type' => 'daily', 'category' => $category, 'date' => $date->copy()->subDay()->toDateString()]) }}" class="btn-outline btn-sm !px-3 !py-2.5" aria-label="Previous day"><span aria-hidden="true">‹</span></a>
                        <input id="date" name="date" type="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()" class="input min-w-0 flex-1 py-2.5 text-sm">
                        @if ($date->lt(today()))
                            <a href="{{ route('reports.index', ['type' => 'daily', 'category' => $category, 'date' => $date->copy()->addDay()->toDateString()]) }}" class="btn-outline btn-sm !px-3 !py-2.5" aria-label="Next day"><span aria-hidden="true">›</span></a>
                        @else
                            <span class="btn-outline btn-sm cursor-not-allowed !px-3 !py-2.5 opacity-50" aria-disabled="true"><span aria-hidden="true">›</span></span>
                        @endif
                    </div>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="from" class="mb-1.5 block text-xs font-bold text-ink">From Date</label>
                        <input id="from" name="from" type="date" value="{{ $from->toDateString() }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()" class="input min-w-0 py-2.5 text-sm">
                    </div>
                    <div>
                        <label for="to" class="mb-1.5 block text-xs font-bold text-ink">To Date</label>
                        <input id="to" name="to" type="date" value="{{ $to->toDateString() }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()" class="input min-w-0 py-2.5 text-sm">
                    </div>
                </div>
            @endif
        </div>
    </form>

    @if ($type === 'daily')
        {{-- ================= DAILY SALES & STOCK ================= --}}
        @php $cols = count($daily['columns']); @endphp
        <section class="card mt-5 overflow-hidden">
            <div class="flex flex-wrap items-end justify-between gap-3 px-6 pb-4 pt-5">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-muted">{{ $date->format('l') }}</p>
                    <h2 class="font-display text-2xl font-bold text-brand-dark">{{ $date->format('F j, Y') }}</h2>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold">
                    <span class="pill-green"><span class="h-1.5 w-1.5 rounded-full bg-brand"></span> Sold today</span>
                    <span class="pill-warn"><span class="h-1.5 w-1.5 rounded-full bg-warn"></span> Low stock / Expiring soon</span>
                    <span class="pill-red"><span class="h-1.5 w-1.5 rounded-full bg-danger"></span> Expired</span>
                </div>
            </div>

            <div class="grid border-t-2 border-brand-dark {{ $cols === 3 ? 'lg:grid-cols-3 lg:divide-x lg:divide-mint-2' : '' }}">
                @foreach ($daily['columns'] as $key => $col)
                    <div class="min-w-0 border-b border-mint-2 lg:border-b-0">
                        <div class="flex items-baseline justify-between gap-2 px-4 pb-2 pt-4">
                            <h3 class="font-display text-lg font-bold text-ink underline decoration-2 underline-offset-4">{{ $col['label'] }}</h3>
                            <span class="text-[11px] text-muted">{{ $col['sold'] }} sold • {{ $col['items'] }} {{ $col['items'] === 1 ? 'item' : 'items' }}</span>
                        </div>
                        <div class="grid grid-cols-[minmax(0,1fr)_64px_76px] gap-2 px-4 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-muted">
                            <span>Item</span><span class="text-center">Stock</span><span class="text-right">Sold</span>
                        </div>

                        @forelse ($col['rows'] as $r)
                            @php
                                $rowClass = match ($r['state']) {
                                    'sold'    => 'border-l-brand bg-mint-2/70',
                                    'alert'   => 'border-l-warn bg-warn-bg',
                                    'expired' => 'border-l-danger bg-danger-bg',
                                    default   => 'border-l-transparent bg-white',
                                };
                            @endphp
                            <div class="grid grid-cols-[minmax(0,1fr)_64px_76px] items-center gap-2 border-l-4 border-t border-t-mint-2/70 px-3 py-2.5 {{ $rowClass }}">
                                <div class="min-w-0">
                                    <p class="break-words text-[13px] font-semibold leading-snug text-ink [overflow-wrap:anywhere]">{{ $r['name'] }}</p>
                                    <p class="mt-0.5 text-[10px] leading-snug {{ $r['expired'] ? 'text-danger' : ($r['expiring'] ? 'text-warn' : 'text-muted') }}">
                                        ₱{{ number_format($r['price'], 2) }} / {{ $r['unit'] }} •
                                        @if ($r['expired']) Expired {{ $r['expires']->format('M d, Y') }}
                                        @else Exp. {{ $r['expires']->format('M d, Y') }}@if ($r['expiring']) ({{ $r['days_left'] }}d)@endif
                                        @endif
                                    </p>
                                </div>
                                <div class="text-center leading-tight">
                                    <span class="font-display text-lg font-bold {{ $r['state'] === 'alert' || ($r['low'] && $r['state'] !== 'sold') ? 'text-warn' : 'text-ink' }}">{{ number_format($r['stock']) }}</span>
                                    @if ($r['qty'] > 0)
                                        <span class="block text-[10px] font-semibold text-brand-dark">−{{ $r['qty'] }}</span>
                                    @elseif ($r['low'])
                                        <span class="block text-[10px] font-semibold text-warn">low</span>
                                    @endif
                                </div>
                                <div class="text-right text-[13px] font-bold {{ $r['qty'] > 0 ? 'text-brand' : 'text-muted' }}">
                                    {{ $r['qty'] > 0 ? number_format($r['net'], 2) : '—' }}
                                </div>
                            </div>
                        @empty
                            <p class="border-t border-mint-2 px-4 py-10 text-center text-xs text-muted">No items in this category.</p>
                        @endforelse

                        <div class="flex items-center justify-between border-t-2 border-brand-dark bg-mint-3 px-4 py-3 text-xs">
                            <span class="font-bold uppercase tracking-wider text-body">{{ $col['label'] }}</span>
                            <span class="font-display text-lg font-bold text-brand-dark">₱{{ number_format($col['total'], 2) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap items-end justify-between gap-6 bg-mint-3 px-6 py-5">
                <p class="max-w-md text-[11px] leading-5 text-body">
                    Every sale (Branded, Generic or Grocery) automatically lowers the stock here.<br>
                    Stock = what's left at the end of the day; − = sold that day. Sold amounts are net of Senior/PWD discount.
                </p>
                <dl class="min-w-[220px] text-sm">
                    @foreach ($daily['columns'] as $col)
                        <div class="flex justify-between gap-8 py-0.5 text-body"><dt>{{ $col['label'] }}</dt><dd class="font-semibold text-ink">₱{{ number_format($col['total'], 2) }}</dd></div>
                    @endforeach
                    <div class="mt-1 flex justify-between gap-8 border-t-2 border-ink pt-1.5"><dt class="font-display font-bold text-ink">Total</dt><dd class="font-display text-lg font-bold text-brand-dark">₱{{ number_format($daily['grand'], 2) }}</dd></div>
                </dl>
            </div>
        </section>
    @else
        {{-- ================= DETAILED / SUMMARY ================= --}}
        <div class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-brand/25 bg-gradient-to-r from-mint-2 to-mint-3 px-5 py-4">
            <div class="flex items-center gap-4">
                <span class="grid h-12 w-12 place-items-center rounded-xl bg-brand text-white"><x-icon name="cash" class="h-6 w-6" /></span>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-body">Calculated Revenue <span class="font-medium normal-case tracking-normal text-muted">(net, after Senior/PWD discount)</span></p>
                    <p class="font-display text-[26px] font-bold leading-tight text-brand-deep">₱{{ number_format($revenue, 2) }}</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-mint-2 bg-white px-4 py-1.5 text-xs font-semibold text-ink">
                <span class="h-2 w-2 rounded-full bg-brand"></span> Showing {{ number_format($rows->total()) }} {{ $rows->total() === 1 ? 'record' : 'records' }} for this period
            </span>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            @foreach ($categories as $k => $label)
                @continue($k === 'all')
                <a href="{{ route('reports.index', ['type' => $type, 'category' => $k, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                   class="card flex items-center justify-between px-5 py-4 transition hover:border-brand/40 {{ $category === $k ? 'border-brand ring-1 ring-brand/20' : '' }}">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted">{{ $label }}</p>
                        <p class="font-display text-xl font-bold text-ink">₱{{ number_format($tiles[$k] ?? 0, 2) }}</p>
                    </div>
                    <span class="text-xs text-muted">View only</span>
                </a>
            @endforeach
        </div>

        <div class="card mt-5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-sm">
                    <thead class="bg-mint-3">
                        <tr>
                            @if ($type === 'detailed') <th class="th px-6 py-4">Date &amp; Time</th><th class="th px-6 py-4">Invoice #</th> @endif
                            <th class="th px-6 py-4">Product</th>
                            <th class="th px-6 py-4 text-center">Quantity</th>
                            <th class="th px-6 py-4 text-right">Amount</th>
                            <th class="th px-6 py-4 text-right">Discount</th>
                            <th class="th px-6 py-4 text-right">Net</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mint-2">
                        @forelse ($rows as $r)
                            <tr class="odd:bg-white even:bg-mint-3/50">
                                @if ($type === 'detailed')
                                    <td class="whitespace-nowrap px-6 py-4 text-ink">
                                        <span class="flex items-center gap-2"><x-icon name="calendar-check" class="h-4 w-4 text-brand" />
                                            <span>{{ \Illuminate\Support\Carbon::parse($r->sold_at)->format('M d, Y') }}<br><span class="text-xs text-muted">{{ \Illuminate\Support\Carbon::parse($r->sold_at)->format('h:i A') }}</span></span>
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 font-mono text-xs font-bold text-ink"><a href="{{ route('sales.show', $r->sale_id) }}" class="hover:text-brand-dark hover:underline">#{{ $r->invoice_no }}</a></td>
                                @endif
                                <td class="px-6 py-4">
                                    <p class="flex flex-wrap items-center gap-2 font-display font-bold text-ink">{{ $r->product_name }} @if ($type === 'detailed') <x-type-pill :type="$r->product_type" /> @endif</p>
                                    @if ($type === 'detailed' && ($r->subtitle || $r->unit))
                                        <p class="text-xs text-muted">{{ collect([$r->subtitle, $r->unit ? 'per ' . $r->unit : null])->filter()->implode(' • ') }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center"><span class="pill-green">{{ number_format($r->quantity) }}</span></td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-ink">₱{{ number_format($r->line_total, 2) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right {{ (float) $r->discount > 0 ? 'font-semibold text-warn' : 'text-muted' }}">{{ (float) $r->discount > 0 ? '−₱' . number_format($r->discount, 2) : '—' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right font-display text-base font-bold text-brand">₱{{ number_format($r->net_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-16 text-center text-muted">No sales found for the selected dates.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-mint-2 px-6 py-4">
                <p class="flex items-center gap-2 text-xs text-body"><x-icon name="info" class="h-4 w-4 text-brand" />
                    {{ $rows->hasMorePages() ? 'More records available on the next page.' : 'End of list for selected dates. Need older sales? Select an earlier “From Date” above.' }}
                </p>
                <div class="flex items-center gap-2">
                    @if ($rows->onFirstPage())
                        <span class="btn-outline btn-sm cursor-not-allowed !px-4 opacity-50">‹ Previous</span>
                    @else
                        <a href="{{ $rows->previousPageUrl() }}" class="btn-outline btn-sm !px-4">‹ Previous</a>
                    @endif
                    <span class="btn-primary btn-sm !px-4">Page {{ $rows->currentPage() }} of {{ max(1, $rows->lastPage()) }}</span>
                    @if ($rows->hasMorePages())
                        <a href="{{ $rows->nextPageUrl() }}" class="btn-outline btn-sm !px-4">Next ›</a>
                    @else
                        <span class="btn-outline btn-sm cursor-not-allowed !px-4 opacity-50">Next ›</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

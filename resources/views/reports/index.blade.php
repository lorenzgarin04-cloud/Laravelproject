@extends('layouts.app')
@section('title', 'Detailed Sales')
@section('crumb-parent', 'Reports')
@section('crumb-parent-url', route('reports.index'))
@section('crumb', 'Detailed Sales')

@section('content')
@php
    $exportQuery = array_filter([
        'type' => $type, 'category' => $category !== 'all' ? $category : null,
        'from' => $from->toDateString(), 'to' => $to->toDateString(),
    ]);
@endphp
<div class="mx-auto min-w-0 max-w-[1100px]">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs">
                <span class="pill-green font-mono uppercase tracking-wider">Reports</span>
                <span class="text-muted">• Q{{ $to->quarter }} {{ $to->year }}</span>
            </div>
            <h1 class="mt-2 font-display text-[32px] font-bold leading-tight text-ink">Detailed Sales</h1>
            <p class="mt-1 text-sm text-body">Every item sold, with Senior/PWD discount and net amount, across dates.</p>
        </div>
        <a href="{{ route('reports.export', $exportQuery) }}" class="btn-primary px-6 py-3.5 shadow-md">
            <x-icon name="download" class="h-4 w-4" /> Export This Report
        </a>
    </div>

    @include('reports._tabs', ['active' => 'detail'])
    <p class="mt-3 text-xs"><a href="{{ route('reports.index') }}" class="font-semibold text-brand-dark hover:underline">← Back to Daily Report</a></p>

    {{-- Parameters --}}
    <form method="GET" action="{{ route('reports.index') }}" class="card mt-4 p-6">
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
        </div>
    </form>

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
</div>
@endsection

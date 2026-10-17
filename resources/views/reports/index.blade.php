@extends('layouts.app')
@section('title', 'Reports')
@section('crumb-parent', 'Overview')
@section('crumb-parent-url', route('dashboard'))
@section('crumb', 'Reports & Analytics')

@section('content')
<div class="mx-auto max-w-[1100px]">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs">
                <span class="pill-green font-mono uppercase tracking-wider">Financial Summary</span>
                <span class="text-muted">• Q{{ $to->quarter }} {{ $to->year }}</span>
            </div>
            <h1 class="mt-2 font-display text-[32px] font-bold leading-tight text-ink">Reports</h1>
            <p class="mt-1 text-sm text-body">Track sales trends, inventory turnover, and dispense history across dates.</p>
        </div>
        <a href="{{ route('reports.export', ['type' => $type, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="btn-primary px-6 py-3.5 shadow-md">
            <x-icon name="download" class="h-4 w-4" /> Export This Report
        </a>
    </div>

    {{-- Parameters --}}
    <form method="GET" action="{{ route('reports.index') }}" class="card mt-6 p-6">
        <div class="flex items-center justify-between border-b border-mint-2 pb-3">
            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-ink"><x-icon name="sliders" class="h-4 w-4 text-brand-dark" /> Report Parameters</h2>
            <span class="text-xs text-muted">Showing live pharmacy dispensary log</span>
        </div>
        <div class="mt-4 grid gap-5 md:grid-cols-[1.1fr_1fr_1fr]">
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
                <label for="from" class="mb-1.5 block text-xs font-bold text-ink">From Date</label>
                <input id="from" name="from" type="date" value="{{ $from->toDateString() }}" max="{{ now()->toDateString() }}" onchange="this.form.submit()" class="input py-2.5 text-sm">
            </div>
            <div>
                <label for="to" class="mb-1.5 block text-xs font-bold text-ink">To Date</label>
                <input id="to" name="to" type="date" value="{{ $to->toDateString() }}" max="{{ now()->toDateString() }}" onchange="this.form.submit()" class="input py-2.5 text-sm">
            </div>
        </div>
    </form>

    {{-- Revenue banner --}}
    <div class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-brand/25 bg-gradient-to-r from-mint-2 to-mint-3 px-5 py-4">
        <div class="flex items-center gap-4">
            <span class="grid h-12 w-12 place-items-center rounded-xl bg-brand text-white"><x-icon name="cash" class="h-6 w-6" /></span>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-body">Calculated Revenue</p>
                <p class="font-display text-[26px] font-bold leading-tight text-brand-deep">₱{{ number_format($revenue, 2) }}</p>
            </div>
        </div>
        <span class="inline-flex items-center gap-2 rounded-full border border-mint-2 bg-white px-4 py-1.5 text-xs font-semibold text-ink">
            <span class="h-2 w-2 rounded-full bg-brand"></span> Showing {{ number_format($rows->total()) }} {{ $rows->total() === 1 ? 'record' : 'records' }} for this period
        </span>
    </div>

    {{-- Table --}}
    <div class="card mt-5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead class="bg-mint-3">
                    <tr>
                        @if ($type === 'detailed') <th class="th px-6 py-4">Date</th> @endif
                        <th class="th px-6 py-4">Product</th>
                        <th class="th px-6 py-4 text-center">Quantity</th>
                        <th class="th px-6 py-4 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mint-2">
                    @forelse ($rows as $r)
                        <tr class="odd:bg-white even:bg-mint-3/50">
                            @if ($type === 'detailed')
                                <td class="whitespace-nowrap px-6 py-4 text-ink">
                                    <span class="flex items-center gap-2"><x-icon name="calendar-check" class="h-4 w-4 text-brand" /> {{ \Illuminate\Support\Carbon::parse($r->sold_at)->format('M d, Y') }}</span>
                                </td>
                            @endif
                            <td class="px-6 py-4">
                                <p class="font-display font-bold text-ink">{{ $r->product_name }}</p>
                                @if ($type === 'detailed' && ($r->subtitle || $r->unit))
                                    <p class="text-xs text-muted">{{ collect([$r->subtitle, $r->unit ? 'per ' . $r->unit : null])->filter()->implode(' • ') }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center"><span class="pill-green">{{ number_format($r->quantity) }}</span></td>
                            <td class="px-6 py-4 text-right font-display text-base font-bold text-brand">₱{{ number_format($r->line_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-16 text-center text-muted">No sales found for the selected dates.</td></tr>
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
                    <span class="btn-outline btn-sm cursor-not-allowed px-4! opacity-50">‹ Previous</span>
                @else
                    <a href="{{ $rows->previousPageUrl() }}" class="btn-outline btn-sm px-4!">‹ Previous</a>
                @endif
                <span class="btn-primary btn-sm px-4!">Page {{ $rows->currentPage() }} of {{ max(1, $rows->lastPage()) }}</span>
                @if ($rows->hasMorePages())
                    <a href="{{ $rows->nextPageUrl() }}" class="btn-outline btn-sm px-4!">Next ›</a>
                @else
                    <span class="btn-outline btn-sm cursor-not-allowed px-4! opacity-50">Next ›</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

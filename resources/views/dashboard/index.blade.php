@extends('layouts.app')
@section('title', 'Dashboard')
@section('crumb-parent', 'Dashboard')
@section('crumb-parent-url', route('dashboard'))
@section('crumb', 'Overview')

@section('content')
<div class="mx-auto max-w-[1100px]">
    {{-- Heading --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-[32px] font-bold leading-tight text-ink">Dashboard</h1>
            <p class="mt-1 text-sm text-body">Here's how your dispensary and inventory are performing today.</p>
        </div>
        <div class="flex items-center gap-2 rounded-lg border border-mint-2 bg-white px-3.5 py-2 text-sm shadow-sm">
            <x-icon name="calendar" class="h-4 w-4 text-brand" />
            <span>Today: <b class="text-ink">{{ now()->format('M d, Y') }}</b> ({{ now()->hour < 18 ? 'Day' : 'Night' }} Shift)</span>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <span class="text-sm text-body">Total Products</span>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-mint-2 text-brand-dark"><x-icon name="archive" /></span>
            </div>
            <p class="mt-4 font-display text-[38px] font-bold leading-none text-ink">{{ number_format($totalProducts) }}</p>
            <span class="pill-green mt-4"><x-icon name="circle-check" class="h-3 w-3" /> In catalog</span>
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between">
                <span class="text-sm text-body">Low Stock Alerts</span>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-warn-bg text-warn"><x-icon name="alert" /></span>
            </div>
            <p class="mt-4 font-display text-[38px] font-bold leading-none text-warn">{{ $lowStockCount }}</p>
            <span class="pill-warn mt-4">Requires Restock</span>
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between">
                <span class="text-sm text-body">Expiring Soon</span>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-danger-bg text-danger"><x-icon name="calendar-x" /></span>
            </div>
            <p class="mt-4 font-display text-[38px] font-bold leading-none text-ink">{{ $expiringCount }}</p>
            <span class="pill-red mt-4">Within {{ \App\Models\Setting::get('expiry_warn_days') }} Days</span>
        </div>

        <div class="card border-brand/30 bg-white p-5 ring-1 ring-brand/10">
            <div class="flex items-center justify-between">
                <span class="text-sm text-body">Today's Sales</span>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-mint-2 text-brand-dark"><x-icon name="cash" /></span>
            </div>
            <p class="mt-4 font-display text-[38px] font-bold leading-none text-brand">₱{{ number_format($todayTotal, 0) }}</p>
            @if (! is_null($delta))
                <span class="pill-green mt-4"><x-icon name="trending" class="h-3 w-3" /> {{ $delta >= 0 ? '+' : '' }}{{ $delta }}% vs yesterday</span>
            @else
                <span class="pill-green mt-4">{{ $todayCount }} sale{{ $todayCount === 1 ? '' : 's' }} today</span>
            @endif
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="mt-9 flex items-center justify-between">
        <h2 class="font-display text-lg font-bold text-ink">Quick Actions</h2>
        <span class="hidden text-xs text-muted sm:block">Frequently used dispensary actions</span>
    </div>
    <div class="mt-3 grid gap-4 sm:grid-cols-3">
        <a href="{{ route('products.create') }}" class="btn-primary py-3.5 text-[15px] shadow-md"><x-icon name="plus" class="h-5 w-5" /> + Add Product</a>
        <a href="{{ route('sales.create') }}" class="btn-outline py-3.5 text-[15px]"><x-icon name="receipt" class="h-5 w-5 text-brand-dark" /> Record Sale (POS)</a>
        <a href="{{ route('reports.index') }}" class="btn-outline py-3.5 text-[15px]"><x-icon name="file" class="h-5 w-5 text-brand-dark" /> View Full Reports</a>
    </div>

    {{-- Recent + Alerts --}}
    <div class="mt-7 grid items-start gap-6 xl:grid-cols-[1fr_330px]">
        <section class="card flex min-h-[420px] flex-col p-6">
            <div class="flex items-center justify-between">
                <h2 class="flex items-center gap-2.5 font-display text-lg font-bold text-ink"><x-icon name="receipt" class="h-5 w-5 text-brand-dark" /> Recent Transactions</h2>
                <a href="{{ route('sales.index') }}" class="flex items-center gap-1 text-sm font-semibold text-brand-dark hover:underline">View All <x-icon name="arrow-right" class="h-3.5 w-3.5" /></a>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead class="bg-mint-3">
                        <tr><th class="th">Invoice #</th><th class="th">Time</th><th class="th">Items</th><th class="th text-right">Total</th><th class="th text-center">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-mint-2">
                    @forelse ($recentSales as $sale)
                        <tr class="align-top">
                            <td class="px-4 py-4 font-display font-bold text-ink"><a href="{{ route('sales.show', $sale) }}" class="hover:text-brand-dark">#{{ $sale->invoice_no }}</a></td>
                            <td class="px-4 py-4 text-muted">{{ $sale->created_at->format('h:i A') }}</td>
                            <td class="px-4 py-4 text-ink">{{ $sale->items->map(fn ($i) => $i->product_name . ($i->quantity > 1 ? " ({$i->quantity}×)" : ''))->implode(', ') }}</td>
                            <td class="px-4 py-4 text-right font-display font-bold text-brand">₱{{ number_format($sale->total, 2) }}</td>
                            <td class="px-4 py-4 text-center"><span class="pill-green capitalize">{{ $sale->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-14 text-center text-muted">No transactions yet. <a href="{{ route('sales.create') }}" class="font-semibold text-brand-dark hover:underline">Record your first sale</a>.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-auto flex items-center justify-between border-t border-mint-2 pt-4 text-xs">
                <span class="text-muted">Showing {{ $recentSales->count() }} of {{ $todayCount }} transactions today</span>
                <span class="font-semibold text-brand-dark">Today's Collection: ₱{{ number_format($todayTotal, 2) }}</span>
            </div>
        </section>

        <aside class="card p-5">
            <div class="flex items-center justify-between">
                <h2 class="flex items-center gap-2 font-display text-base font-bold text-ink"><x-icon name="bell" class="h-4 w-4 text-warn" /> Urgent Alerts</h2>
                <span class="pill-warn">{{ $criticalCount }} Critical</span>
            </div>

            <div class="mt-4 space-y-3">
                @foreach ($lowStockProducts as $p)
                    <div class="flex items-start justify-between gap-3 rounded-lg border border-warn-line bg-warn-bg p-3">
                        <div class="flex gap-2.5">
                            <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-danger" />
                            <div>
                                <p class="text-sm font-semibold leading-snug text-ink">{{ $p->name }}</p>
                                <p class="mt-0.5 text-[11px] text-muted">{{ $p->stock }} unit{{ $p->stock === 1 ? '' : 's' }} remain (Reorder at: {{ \App\Models\Setting::get('low_stock_below') }})</p>
                            </div>
                        </div>
                        <a href="{{ route('products.edit', $p) }}" class="btn-outline btn-sm border-danger-line! text-danger">Restock</a>
                    </div>
                @endforeach

                @foreach ($expiringProducts as $p)
                    @php $days = (int) now()->startOfDay()->diffInDays($p->expiration_date, false); @endphp
                    <div class="flex items-start justify-between gap-3 rounded-lg border border-danger-line bg-danger-bg p-3">
                        <div class="flex gap-2.5">
                            <x-icon name="calendar-x" class="mt-0.5 h-4 w-4 shrink-0 text-danger" />
                            <div>
                                <p class="text-sm font-semibold leading-snug text-ink">{{ $p->name }}</p>
                                <p class="mt-0.5 text-[11px] font-medium text-danger">{{ $days < 0 ? 'Expired ' . abs($days) . ' days ago' : "Expires in {$days} days" }} ({{ $p->stock }} units)</p>
                            </div>
                        </div>
                        <a href="{{ route('products.show', $p) }}" class="btn-outline btn-sm border-danger-line! text-danger">Review</a>
                    </div>
                @endforeach

                @if ($criticalCount === 0)
                    <p class="rounded-lg border border-mint-2 bg-mint-3 p-4 text-center text-sm text-muted">All clear — no urgent alerts.</p>
                @endif
            </div>
        </aside>
    </div>
</div>
@endsection

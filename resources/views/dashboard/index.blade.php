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
        <a href="{{ route('products.index') }}" class="card group block p-5 transition hover:-translate-y-0.5 hover:border-brand/50 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40" aria-label="View all products">
            <div class="flex items-center justify-between">
                <span class="text-sm text-body">Total Products</span>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-mint-2 text-brand-dark"><x-icon name="archive" /></span>
            </div>
            <p class="mt-4 font-display text-[38px] font-bold leading-none text-ink">{{ number_format($totalProducts) }}</p>
            <span class="pill-green mt-4"><x-icon name="circle-check" class="h-3 w-3" /> In catalog</span>
            <span class="mt-3 block text-xs font-semibold text-brand-dark group-hover:underline">View products →</span>
        </a>

        <a href="{{ route('products.index', ['filter' => 'low']) }}" class="card group block p-5 transition hover:-translate-y-0.5 hover:border-brand/50 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40" aria-label="View low stock products">
            <div class="flex items-center justify-between">
                <span class="text-sm text-body">Low Stock Alerts</span>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-warn-bg text-warn"><x-icon name="alert" /></span>
            </div>
            <p class="mt-4 font-display text-[38px] font-bold leading-none text-warn">{{ $lowStockCount }}</p>
            <span class="pill-warn mt-4">Requires Restock</span>
            <span class="mt-3 block text-xs font-semibold text-brand-dark group-hover:underline">View low stock →</span>
        </a>

        <a href="{{ route('products.index', ['filter' => 'expiring']) }}" class="card group block p-5 transition hover:-translate-y-0.5 hover:border-brand/50 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40" aria-label="View products expiring soon">
            <div class="flex items-center justify-between">
                <span class="text-sm text-body">Expiring Soon</span>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-danger-bg text-danger"><x-icon name="calendar-x" /></span>
            </div>
            <p class="mt-4 font-display text-[38px] font-bold leading-none text-ink">{{ $expiringCount }}</p>
            <span class="pill-red mt-4">Within {{ \App\Models\Setting::get('expiry_warn_days') }} Days</span>
            @if ($expiredCount > 0) <span class="pill-red mt-2 border-danger bg-danger text-white">{{ $expiredCount }} already expired</span> @endif
            <span class="mt-3 block text-xs font-semibold text-brand-dark group-hover:underline">View expiring →</span>
        </a>

        <a href="{{ route('reports.index') }}" class="card group block p-5 transition hover:-translate-y-0.5 hover:border-brand/50 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40 border-brand/30 ring-1 ring-brand/10" aria-label="Open today's Daily Report">
            <div class="flex items-center justify-between">
                <span class="text-sm text-body">Today's Sales</span>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-mint-2 text-brand-dark"><x-icon name="cash" /></span>
            </div>
            <p class="mt-4 font-display text-[38px] font-bold leading-none {{ ($delta ?? 0) < 0 ? 'text-danger' : 'text-brand' }}">₱{{ number_format($todayTotal, 0) }}</p>
            @if (! is_null($delta))
                <span class="{{ $delta < 0 ? 'pill-red' : 'pill-green' }} mt-4"><x-icon name="{{ $delta < 0 ? 'trending-down' : 'trending' }}" class="h-3 w-3" /> {{ $delta > 0 ? '+' : '' }}{{ $delta }}% vs yesterday</span>
            @else
                <span class="pill-green mt-4">{{ $todayCount }} sale{{ $todayCount === 1 ? '' : 's' }} today</span>
            @endif
            <span class="mt-3 block text-xs font-semibold text-brand-dark group-hover:underline">View Daily Report →</span>
        </a>
    </div>

    {{-- Quick actions --}}
    <div class="mt-9 flex items-center justify-between">
        <h2 class="font-display text-lg font-bold text-ink">Quick Actions</h2>
        <span class="hidden text-xs text-muted sm:block">Frequently used dispensary actions</span>
    </div>
    <div class="mt-3 grid gap-4 {{ auth()->user()->isAdmin() ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }}">
        @if (auth()->user()->isAdmin())
            <a href="{{ route('products.create') }}" class="btn-primary py-3.5 text-[15px] shadow-md"><x-icon name="plus" class="h-5 w-5" /> Add Product</a>
        @endif
        <a href="{{ route('sales.create') }}" class="btn-outline py-3.5 text-[15px]"><x-icon name="receipt" class="h-5 w-5 text-brand-dark" /> Record Sale (POS)</a>
        <a href="{{ route('reports.index') }}" class="btn-outline py-3.5 text-[15px]"><x-icon name="file" class="h-5 w-5 text-brand-dark" /> Today's Report</a>
    </div>

    {{-- Recent + Alerts --}}
    <div class="mt-7 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_330px]">
        <div class="min-w-0 space-y-6">
        <section class="card min-w-0 p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="flex items-center gap-2.5 font-display text-lg font-bold text-ink"><x-icon name="chart" class="h-5 w-5 text-brand-dark" /> Today's Sales by Type</h2>
                    <p class="mt-1 text-xs text-muted">Branded, Generic and Grocery — same totals as today's Daily Report.</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('reports.index') }}" class="btn-outline btn-sm"><x-icon name="file" class="h-3.5 w-3.5" /> Daily Report</a>
                    <a href="{{ route('reports.analytics') }}" class="btn-soft btn-sm"><x-icon name="chart" class="h-3.5 w-3.5" /> Analytics</a>
                </div>
            </div>

            @php $typeDots = ['branded' => '#00945c', 'generic' => '#2b72d6', 'grocery' => '#ee6c35']; @endphp
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach ($typeToday['types'] as $key => $row)
                    <a href="{{ route('reports.index', ['category' => $key]) }}" class="block rounded-xl border border-mint-2 bg-mint-3/60 p-4 transition hover:border-brand/40 hover:bg-mint-2/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40" aria-label="Open {{ $row['label'] }} in the Daily Report">
                        <p class="flex items-center gap-2 text-xs font-bold text-ink"><span class="h-2.5 w-2.5 rounded-sm" style="background: {{ $typeDots[$key] }}"></span> {{ $row['label'] }}</p>
                        <p class="mt-2 font-display text-2xl font-bold leading-none text-ink">₱{{ number_format($row['net'], 2) }}</p>
                        <p class="mt-1.5 text-[11px] text-muted">{{ number_format($row['qty']) }} {{ $row['qty'] === 1 ? 'item' : 'items' }} sold</p>
                    </a>
                @endforeach
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-brand/25 bg-mint-2/70 px-4 py-3 text-sm">
                <span class="font-semibold text-ink">Total today</span>
                <span class="font-display text-lg font-bold text-brand-dark">₱{{ number_format($typeToday['total'], 2) }} <span class="text-xs font-medium text-muted">• {{ $typeToday['sales'] }} {{ $typeToday['sales'] === 1 ? 'sale' : 'sales' }}</span></span>
            </div>
        </section>

        <section class="card flex min-h-[420px] min-w-0 flex-col p-6">
            <div class="flex items-center justify-between">
                <h2 class="flex items-center gap-2.5 font-display text-lg font-bold text-ink"><x-icon name="receipt" class="h-5 w-5 text-brand-dark" /> Recent Transactions</h2>
                <a href="{{ route('sales.index') }}" class="flex items-center gap-1 text-sm font-semibold text-brand-dark hover:underline">View All <x-icon name="arrow-right" class="h-3.5 w-3.5" /></a>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full min-w-[620px] text-sm">
                    <thead class="bg-mint-3">
                        <tr><th class="th">Invoice #</th><th class="th">Date &amp; Time</th><th class="th">Items</th><th class="th text-right">Total</th><th class="th text-center">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-mint-2">
                    @forelse ($recentSales as $sale)
                        <tr class="align-top">
                            <td class="whitespace-nowrap px-4 py-4 font-display font-bold text-ink"><a href="{{ route('sales.show', $sale) }}" class="hover:text-brand-dark">#{{ $sale->invoice_no }}</a></td>
                            <td class="whitespace-nowrap px-4 py-4 text-muted">{{ $sale->created_at->isToday() ? 'Today' : $sale->created_at->format('M d') }}<br><span class="text-xs">{{ $sale->created_at->format('h:i A') }}</span></td>
                            <td class="px-4 py-4 text-ink">{{ $sale->items->map(fn ($i) => $i->product_name . ($i->quantity > 1 ? " ({$i->quantity}×)" : ''))->implode(', ') }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right font-display font-bold text-brand">₱{{ number_format($sale->total, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-center"><span class="pill-green whitespace-nowrap capitalize">{{ $sale->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-14 text-center text-muted">No transactions yet. <a href="{{ route('sales.create') }}" class="font-semibold text-brand-dark hover:underline">Record your first sale</a>.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-auto flex items-center justify-between border-t border-mint-2 pt-4 text-xs">
                <span class="text-muted">Latest {{ $recentSales->count() }} {{ $recentSales->count() === 1 ? 'transaction' : 'transactions' }} • {{ $todayCount }} today</span>
                <span class="font-semibold text-brand-dark">Today's Collection: ₱{{ number_format($todayTotal, 2) }}</span>
            </div>
        </section>

        </div>

        <aside class="card min-w-0 p-5">
            <div class="flex items-center justify-between">
                <h2 class="flex items-center gap-2 font-display text-base font-bold text-ink"><x-icon name="bell" class="h-4 w-4 text-warn" /> Urgent Alerts</h2>
                <span class="pill-warn">{{ $criticalCount }} Critical</span>
            </div>

            <div class="mt-4 max-h-[480px] space-y-3 overflow-y-auto pr-1">
                @foreach ($lowStockProducts as $p)
                    <div class="flex items-start justify-between gap-3 rounded-lg border border-warn-line bg-warn-bg p-3">
                        <div class="flex min-w-0 gap-2.5">
                            <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-danger" />
                            <div>
                                <p class="break-words text-sm font-semibold leading-snug text-ink [overflow-wrap:anywhere]">{{ $p->name }}</p>
                                <p class="mt-0.5 text-[11px] text-muted">{{ $p->stock }} unit{{ $p->stock === 1 ? '' : 's' }} remain (Reorder at: {{ \App\Models\Setting::get('low_stock_below') }})</p>
                            </div>
                        </div>
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('products.edit', $p) }}" class="btn-outline btn-sm border-danger-line! text-danger">Restock</a>
                        @endif
                    </div>
                @endforeach

                @foreach ($expiringProducts as $p)
                    @php $days = (int) now()->startOfDay()->diffInDays($p->expiration_date, false); @endphp
                    <div class="flex items-start justify-between gap-3 rounded-lg border border-danger-line bg-danger-bg p-3">
                        <div class="flex min-w-0 gap-2.5">
                            <x-icon name="calendar-x" class="mt-0.5 h-4 w-4 shrink-0 text-danger" />
                            <div>
                                <p class="break-words text-sm font-semibold leading-snug text-ink [overflow-wrap:anywhere]">{{ $p->name }}</p>
                                <p class="mt-0.5 text-[11px] font-medium text-danger">{{ $days < 0 ? 'Expired ' . abs($days) . (abs($days) === 1 ? ' day ago' : ' days ago') : ($days === 0 ? 'Expires today' : "Expires in {$days} " . ($days === 1 ? 'day' : 'days')) }} ({{ $p->stock }} units)</p>
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

@extends('layouts.app')
@section('title', 'Sales History')
@section('crumb-parent', 'Sales')
@section('crumb-parent-url', route('sales.create'))
@section('crumb', 'Sales History')

@section('content')
<div class="mx-auto max-w-[1100px]">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Sales History</h1>
            <p class="page-sub">All completed transactions, newest first.</p>
        </div>
        <a href="{{ route('sales.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Record Sale</a>
    </div>

    <div class="card mt-6 overflow-x-auto">
        <table class="w-full min-w-[640px] text-sm">
            <thead class="bg-mint-3">
                <tr><th class="th">Invoice #</th><th class="th">Date &amp; Time</th><th class="th">Cashier</th><th class="th text-center">Items</th><th class="th text-right">Total</th><th class="th text-center">Status</th><th class="th"></th></tr>
            </thead>
            <tbody class="divide-y divide-mint-2">
                @forelse ($sales as $sale)
                    <tr class="hover:bg-mint/60">
                        <td class="px-4 py-3.5 font-display font-bold text-ink">#{{ $sale->invoice_no }}</td>
                        <td class="px-4 py-3.5 text-muted">{{ $sale->created_at->format('M d, Y • h:i A') }}</td>
                        <td class="px-4 py-3.5 text-ink">{{ $sale->cashier->name }}</td>
                        <td class="px-4 py-3.5 text-center">{{ $sale->items->sum('quantity') }}</td>
                        <td class="px-4 py-3.5 text-right font-display font-bold text-brand">₱{{ number_format($sale->total, 2) }}</td>
                        <td class="px-4 py-3.5 text-center"><span class="pill-green capitalize">{{ $sale->status }}</span></td>
                        <td class="px-4 py-3.5 text-right"><a href="{{ route('sales.show', $sale) }}" class="btn-soft btn-sm">Receipt</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-14 text-center text-muted">No sales recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $sales->links() }}</div>
</div>
@endsection

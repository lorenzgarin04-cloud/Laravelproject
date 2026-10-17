@extends('layouts.app')
@section('title', 'Receipt #' . $sale->invoice_no)
@section('crumb-parent', 'Sales')
@section('crumb-parent-url', route('sales.index'))
@section('crumb', 'Receipt Export')

@section('content')
<div class="mx-auto max-w-[900px]">
    <h1 class="page-title text-[28px]! text-brand">Receipt Export Screen</h1>
    <p class="page-sub">Review and download the customer sales receipt as a verified printable record.</p>

    <div id="receipt" class="mx-auto mt-6 max-w-[400px] rounded-2xl border border-mint-2 bg-white p-6 shadow-[0_6px_20px_rgba(0,0,0,.08)]">
        <div class="flex items-center justify-between border-b border-mint-2 pb-3 text-xs">
            <span class="pill-green"><x-icon name="receipt" class="h-3 w-3" /> Customer Sales Slip</span>
            <span class="text-muted">Standard 80mm Slip</span>
        </div>

        <div class="mt-5 text-center">
            <span class="mx-auto grid h-11 w-11 place-items-center rounded-xl bg-brand text-white"><x-icon name="pharmacy" class="h-6 w-6" /></span>
            <h2 class="mt-3 font-display text-xl font-bold text-ink">{{ \App\Models\Setting::get('store_name') }}</h2>
            <p class="text-xs font-medium text-brand">{{ \App\Models\Setting::get('store_tagline') }}</p>
            <p class="mt-1 text-[11px] leading-4 text-muted">{{ \App\Models\Setting::get('store_address') }}<br>Tel: {{ \App\Models\Setting::get('store_phone') }} • VAT Reg: {{ \App\Models\Setting::get('store_tin') }}</p>
        </div>

        <dl class="mt-5 space-y-1.5 rounded-lg border border-brand/20 bg-mint-3 p-3.5 text-xs">
            <div class="flex justify-between"><dt class="text-body">Receipt Number:</dt><dd class="font-mono font-bold text-ink">#{{ $sale->invoice_no }}</dd></div>
            <div class="flex justify-between"><dt class="text-body">Date &amp; Time:</dt><dd class="font-semibold text-ink">{{ $sale->created_at->format('F d, Y • h:i A') }}</dd></div>
            <div class="flex justify-between"><dt class="text-body">Cashier / Staff:</dt><dd class="font-semibold text-ink">{{ $sale->cashier->name }}</dd></div>
        </dl>

        <table class="mt-5 w-full text-xs">
            <thead><tr class="border-b border-line text-[11px] font-bold uppercase text-ink"><th class="pb-1.5 text-left">Item Description</th><th class="pb-1.5 text-right">Amount</th></tr></thead>
            <tbody class="divide-y divide-mint-2">
                @foreach ($sale->items as $item)
                    <tr>
                        <td class="py-2"><p class="font-bold text-ink">{{ $item->product_name }}</p><p class="text-[11px] text-muted">Qty: {{ $item->quantity }} × ₱{{ number_format($item->unit_price, 2) }}</p></td>
                        <td class="py-2 text-right align-top font-bold text-ink">₱{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <dl class="mt-3 space-y-1 border-t border-mint-2 pt-3 text-xs text-body">
            <div class="flex justify-between"><dt>Subtotal:</dt><dd class="font-semibold text-ink">₱{{ number_format($sale->subtotal, 2) }}</dd></div>
            @if ($sale->senior_pwd)
                <div class="flex justify-between"><dt>Senior / PWD 20% Discount:</dt><dd class="font-semibold text-brand-dark">-₱{{ number_format($sale->discount, 2) }}</dd></div>
            @endif
            <div class="flex justify-between"><dt>Cash Received:</dt><dd class="font-semibold text-ink">₱{{ number_format($sale->cash_received, 2) }}</dd></div>
            <div class="flex justify-between"><dt>Change Due:</dt><dd class="font-semibold text-brand">₱{{ number_format($sale->change_due, 2) }}</dd></div>
        </dl>

        <div class="mt-4 flex items-center justify-between rounded-lg border border-brand/20 bg-mint-2/70 px-4 py-3.5">
            <span class="font-display text-sm font-bold uppercase text-ink">Total Amount</span>
            <span class="font-display text-2xl font-bold text-brand">₱{{ number_format($sale->total, 2) }}</span>
        </div>

        <div class="mt-5 border-t border-dashed border-line pt-4 text-center">
            <p class="text-xs font-semibold text-brand">Quality Care, Every Day.</p>
            <p class="text-[11px] text-body">Thank you for your purchase!</p>
            <p class="mx-auto mt-3 inline-block rounded bg-mint-2 px-2.5 py-1 font-mono text-[10px] text-body">BARCODE: *DG-{{ $sale->created_at->format('Ymd') }}-{{ str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}*</p>
        </div>
    </div>

    <div class="mx-auto mt-6 flex max-w-[400px] flex-wrap justify-center gap-3">
        <a href="{{ route('sales.create') }}" class="btn-outline btn-sm px-4! py-2.5!"><x-icon name="arrow-left" class="h-4 w-4" /> Back to Sales</a>
        <button type="button" onclick="window.print()" class="btn-outline btn-sm px-4! py-2.5!"><x-icon name="printer" class="h-4 w-4" /> Print Slip</button>
        <button type="button" onclick="document.title='Receipt-{{ $sale->invoice_no }}'; window.print()" class="btn-primary btn-sm px-4! py-2.5!" title="In the print dialog, choose “Save as PDF”"><x-icon name="download" class="h-4 w-4" /> Download PDF</button>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', $product->name)
@section('crumb-parent', 'Products')
@section('crumb-parent-url', route('products.index'))
@section('crumb', 'Product Details')

@section('content')
@php
    $badge = match ($product->status) {
        'out_of_stock' => ['Out of Stock', 'bg-danger text-white'],
        'low_stock' => ['Low Stock', 'bg-warn text-white'],
        'expiring'  => ['Expiring Soon', 'bg-danger text-white'],
        default     => ['In Stock', 'bg-brand-dark text-white'],
    };
@endphp
<div class="mx-auto max-w-[900px]">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 text-xs">
                <a href="{{ route('products.index') }}" class="pill-green"><x-icon name="arrow-left" class="h-3 w-3" /> Back to Products</a>
                <span class="text-muted">Item code: {{ $product->sku }}</span>
            </div>
            <h1 class="mt-3 font-display text-[32px] font-bold leading-tight text-brand-deep">Product Details</h1>
            <p class="mt-1 text-sm text-body">Review stock level, pharmaceutical specifications, and dispensary instructions.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('products.index') }}" class="btn-soft"><x-icon name="arrow-left" class="h-4 w-4" /> Back</a>
            <a href="{{ route('products.edit', $product) }}" class="btn-primary"><x-icon name="edit" class="h-4 w-4" /> Edit Product</a>
        </div>
    </div>

    <section class="card mt-6 grid gap-8 p-7 md:grid-cols-[230px_1fr]">
        <div>
            <div class="relative rounded-xl border border-brand/20 bg-mint-3 p-3">
                <span class="absolute right-3 top-3 z-10 rounded border border-mint-2 bg-white px-2 py-0.5 font-mono text-[11px] text-brand-dark">#{{ $product->sku }}</span>
                <img src="{{ $product->photo_url }}" alt="{{ $product->name }}" class="mt-7 aspect-[4/3] w-full rounded-lg object-cover">
            </div>
            <p class="mt-2 flex items-center justify-center gap-1.5 text-xs text-muted"><x-icon name="shield-check" class="h-3.5 w-3.5 text-brand" /> Clinical Packaging</p>
        </div>

        <div>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="pill-green text-[11px] uppercase">{{ $product->category_label }}</span>
                        @if ($product->unit) <span class="text-muted">{{ ucfirst($product->unit) }} Formulation</span> @endif
                    </div>
                    <h2 class="mt-2 font-display text-[26px] font-bold leading-tight text-ink">{{ $product->name }}</h2>
                    @if ($product->subtitle) <p class="mt-1 text-xs text-body">{{ $product->subtitle }}</p> @endif
                </div>
                <div class="text-right">
                    <p class="font-display text-[34px] font-bold leading-none text-brand">₱{{ number_format($product->price, 2) }}</p>
                    <p class="mt-1 text-xs text-muted">per {{ $product->unit ?: 'piece' }}</p>
                </div>
            </div>

            <div class="mt-5 grid gap-4 rounded-xl border border-brand/20 bg-mint-3 p-4 sm:grid-cols-3 sm:divide-x sm:divide-brand/15">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-lg border border-mint-2 bg-white text-brand-dark"><x-icon name="archive" class="h-4 w-4" /></span>
                    <div><p class="text-[10px] font-bold uppercase tracking-wider text-brand-dark">Current Stock</p><p class="font-display text-sm font-bold text-ink">{{ number_format($product->stock) }} in stock</p></div>
                </div>
                <div class="flex items-center gap-3 sm:pl-4">
                    <span class="grid h-9 w-9 place-items-center rounded-lg border border-mint-2 bg-white text-brand-dark"><x-icon name="calendar-check" class="h-4 w-4" /></span>
                    <div><p class="text-[10px] font-bold uppercase tracking-wider text-brand-dark">Expiration Date</p><p class="font-display text-sm font-bold text-ink">{{ $product->expiration_date->format('M d, Y') }}</p></div>
                </div>
                <div class="sm:pl-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-brand-dark">Store Status</p>
                    <span class="mt-1 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold {{ $badge[1] }}"><span class="h-1.5 w-1.5 rounded-full bg-white"></span> {{ $badge[0] }}</span>
                </div>
            </div>

            <h3 class="mt-6 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-ink"><x-icon name="file" class="h-4 w-4 text-brand-dark" /> Medicine Description &amp; Usage</h3>
            <div class="mt-2 rounded-lg border border-mint-2 border-l-4 border-l-brand bg-mint-3 p-4 text-sm leading-6 text-ink">
                {{ $product->description ?: 'No description provided yet.' }}
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-mint-2 pt-5">
                <a href="{{ route('products.index') }}" class="btn-soft"><x-icon name="arrow-left" class="h-4 w-4" /> Back to Products</a>
                <a href="{{ route('products.edit', $product) }}" class="btn-primary"><x-icon name="edit" class="h-4 w-4" /> Edit Medicine</a>
            </div>
        </div>
    </section>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Edit Product')
@section('crumb-parent', 'Products')
@section('crumb-parent-url', route('products.index'))
@section('crumb', 'Edit Product')

@section('content')
<div class="mx-auto max-w-[780px]" x-data="{ confirmDelete: false }" @open-delete.window="confirmDelete = true">
    <a href="{{ route('products.index') }}" class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-brand hover:underline"><x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back to Products</a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
            <span class="pill-green font-mono text-[11px] uppercase tracking-wider"><span class="h-1.5 w-1.5 rounded-full bg-brand-dark"></span> Inventory Catalog</span>
            <h1 class="mt-2 font-display text-[28px] font-bold leading-tight text-ink">Edit Product</h1>
            <p class="mt-1 text-sm text-body">Update product details or manage inventory status.</p>
        </div>
        <span class="flex items-center gap-2 rounded-lg border border-mint-2 bg-white px-3 py-1.5 font-mono text-xs text-body shadow-sm"><x-icon name="archive" class="h-3.5 w-3.5 text-brand-dark" /> SKU: #{{ $product->sku }}</span>
    </div>

    <div class="mt-6">@include('products._form')</div>

    {{-- Delete confirmation modal --}}
    <div x-show="confirmDelete" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-ink/50 p-5" @keydown.escape.window="confirmDelete = false">
        <div class="w-full max-w-md rounded-2xl bg-white p-7 shadow-2xl" @click.outside="confirmDelete = false" role="dialog" aria-modal="true" aria-labelledby="del-title">
            <span class="grid h-12 w-12 place-items-center rounded-full bg-danger-bg text-danger"><x-icon name="trash" class="h-6 w-6" /></span>
            <h2 id="del-title" class="mt-4 font-display text-xl font-bold text-ink">Delete this product?</h2>
            <p class="mt-2 text-sm leading-6 text-body"><b>{{ $product->name }}</b> will be removed from the catalog and POS. Past sales and receipts will keep their records.</p>
            @unless (auth()->user()->isAdmin())
                <p class="mt-3 rounded-lg border border-warn-line bg-warn-bg p-3 text-xs text-warn">Only an administrator can delete products.</p>
            @endunless
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="confirmDelete = false" class="btn-outline">Keep Product</button>
                <form method="POST" action="{{ route('products.destroy', $product) }}">
                    @csrf @method('DELETE')
                    <button class="btn bg-danger text-white hover:opacity-90"><x-icon name="trash" class="h-4 w-4" /> Yes, Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

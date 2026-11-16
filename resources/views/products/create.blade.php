@extends('layouts.app')
@section('title', 'Add New Product')
@section('crumb-parent', 'Products')
@section('crumb-parent-url', route('products.index'))
@section('crumb', 'Add New Product')

@section('content')
<div class="mx-auto max-w-[780px]">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <span class="pill-green font-mono text-[11px] uppercase tracking-wider"><x-icon name="archive" class="h-3 w-3" /> Inventory Management</span>
            <h1 class="mt-2 font-display text-[32px] font-bold leading-tight text-brand-deep">Add New Product</h1>
            <p class="mt-1 text-sm text-body">Register a new medicine or healthcare item into store inventory.</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn-outline btn-sm"><x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back to Products</a>
    </div>

    <div class="mt-6">@include('products._form')</div>
</div>
@endsection

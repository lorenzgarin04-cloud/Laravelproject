@extends('layouts.app')
@section('title', 'Products')
@section('crumb-parent', 'Products')
@section('crumb-parent-url', route('products.index'))
@section('crumb', 'Store Catalog')

@section('content')
<div class="mx-auto max-w-[1100px]">
    <h1 class="page-title text-[#1b7a3c]!">Products</h1>
    <p class="page-sub text-[15px]">See what you have in stock.</p>

    {{-- Search + Add --}}
    <div class="mt-7 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('products.index') }}" class="relative w-full max-w-[400px] flex-1">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-body" />
            <input type="search" name="q" value="{{ $term }}" placeholder="Search by product name, generic, or SKU…" class="input input-icon py-3.5">
        </form>
        <a href="{{ route('products.create') }}" class="btn-primary px-8 py-3.5 text-base shadow-md">+ Add Product</a>
    </div>

    {{-- Filter chips --}}
    @php
        $chips = [
            'all'      => ['All', 'count-green'],
            'rx'       => ['Rx Prescription', 'count-plain'],
            'otc'      => ['OTC Medicines', 'count-plain'],
            'low'      => ['Low Stock', 'warn'],
            'expiring' => ['Expiring Soon', 'red'],
        ];
    @endphp
    <div class="mt-5 flex flex-wrap gap-2.5">
        @foreach ($chips as $key => [$label, $tone])
            @php
                $active = $filter === $key;
                $cls = match (true) {
                    $active && $tone === 'warn' => 'border-warn bg-warn-bg text-warn',
                    $active && $tone === 'red'  => 'border-danger bg-danger-bg text-danger',
                    $active                     => 'border-brand bg-brand text-white',
                    $tone === 'warn'            => 'border-warn-line bg-warn-bg text-warn',
                    $tone === 'red'             => 'border-danger-line bg-danger-bg text-danger',
                    default                     => 'border-line bg-white text-ink hover:bg-mint',
                };
            @endphp
            <a href="{{ route('products.index', array_filter(['filter' => $key === 'all' ? null : $key, 'q' => $term])) }}"
               class="inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-[13px] font-semibold transition {{ $cls }}">
                @if ($key === 'low') <x-icon name="alert" class="h-3.5 w-3.5" /> @elseif ($key === 'expiring') <x-icon name="hourglass" class="h-3.5 w-3.5" /> @endif
                {{ $label }}
                <span class="rounded-full px-1.5 text-[11px] {{ $active && !in_array($tone, ['warn','red']) ? 'bg-white/25' : 'bg-black/5' }}">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Grid --}}
    @if ($products->isEmpty())
        <div class="card mt-8 px-6 py-20 text-center">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-mint-2 text-brand-dark"><x-icon name="pharmacy" class="h-8 w-8" /></span>
            <h2 class="mt-5 font-display text-xl font-bold text-ink">No products found</h2>
            <p class="mx-auto mt-1 max-w-sm text-sm text-muted">{{ $term ? 'Try a different search term or clear the filters.' : 'Your catalog is empty. Add your first medicine to start tracking stock.' }}</p>
            <a href="{{ route('products.create') }}" class="btn-primary mt-6">+ Add Product</a>
        </div>
    @else
        <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($products as $p)
                <article class="card flex flex-col p-3.5" x-data="stockCard({{ $p->id }}, {{ $p->stock }}, '{{ $p->status }}', '{{ route('products.stock', $p) }}')">
                    <div class="relative overflow-hidden rounded-lg bg-mint-3">
                        <a href="{{ route('products.show', $p) }}"><img src="{{ $p->photo_url }}" alt="{{ $p->name }}" loading="lazy" class="aspect-square w-full object-cover"></a>
                        <a href="{{ route('products.edit', $p) }}" class="absolute right-2 top-2 grid h-8 w-8 place-items-center rounded-full bg-white text-ink shadow hover:bg-mint" aria-label="Edit {{ $p->name }}">
                            <x-icon name="edit" class="h-3.5 w-3.5" />
                        </a>
                    </div>

                    <div class="mt-3 flex items-start justify-between gap-2 px-1">
                        <a href="{{ route('products.show', $p) }}" class="font-display text-base font-bold leading-snug text-ink hover:text-brand-dark">{{ $p->name }}</a>
                        <span class="pill shrink-0 text-[11px]" :class="badge.cls" x-text="badge.text"></span>
                    </div>
                    <p class="mt-1.5 px-1 font-display text-lg font-bold text-brand">₱{{ number_format($p->price, 2) }}</p>

                    <div class="mt-auto flex items-center justify-between border-t border-mint-2 px-1 pt-3">
                        <button type="button" @click="change(-1)" :disabled="busy || stock <= 0" class="grid h-10 w-10 place-items-center rounded-full border border-line bg-mint text-ink transition hover:bg-mint-2 disabled:opacity-40" aria-label="Decrease stock"><x-icon name="minus" class="h-4 w-4" /></button>
                        <span class="font-display text-lg font-bold text-ink" x-text="stock" aria-live="polite"></span>
                        <button type="button" @click="change(1)" :disabled="busy" class="grid h-10 w-10 place-items-center rounded-full bg-brand text-white transition hover:bg-brand-dark disabled:opacity-50" aria-label="Increase stock"><x-icon name="plus" class="h-4 w-4" /></button>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">{{ $products->links() }}</div>
    @endif
</div>

@push('scripts')
<script>
function stockCard(id, stock, status, url) {
    const BADGES = {
        in_stock:  { text: 'In Stock',      cls: 'border-brand/25 bg-mint-2 text-brand-dark' },
        low_stock: { text: 'Low Stock',     cls: 'border-warn-line bg-warn-bg text-warn' },
        expiring:  { text: 'Expiring Soon', cls: 'border-danger-line bg-danger-bg text-danger' },
        out_of_stock: { text: 'Out of Stock', cls: 'border-danger-line bg-danger-bg text-danger' },
    };
    return {
        stock, status, busy: false,
        get badge() { return BADGES[this.status] ?? BADGES.in_stock; },
        async change(delta) {
            if (this.busy) return;
            this.busy = true;
            try {
                const res = await window.api(url, { method: 'PATCH', body: { delta } });
                this.stock = res.stock;
                this.status = res.status;
            } catch (e) { alert(e.message); }
            this.busy = false;
        },
    };
}
</script>
@endpush
@endsection

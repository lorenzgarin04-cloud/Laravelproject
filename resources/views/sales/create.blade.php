@extends('layouts.app')
@section('title', 'Record a Sale')
@section('crumb-parent', 'Sales')
@section('crumb-parent-url', route('sales.index'))
@section('crumb', 'Record a Sale')

@section('content')
<div class="mx-auto max-w-[1100px]" x-data="pos()" x-cloak>
    <h1 class="page-title">Record a Sale</h1>
    <p class="page-sub text-[15px]">Add items and complete the sale.</p>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_390px]">

        {{-- ================= LEFT ================= --}}
        <div class="space-y-5">
            <div class="card p-4">
                <label for="q" class="mb-2 block text-sm font-bold text-ink">Search Products</label>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-brand" />
                    <input id="q" type="search" x-model="q" @input.debounce.250ms="search()" placeholder="Search for a product to add…" autocomplete="off"
                           class="input input-icon border-2! border-brand! py-3.5 shadow-none">
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-mint-2 bg-mint-3 px-5 py-4">
                    <h2 class="font-display text-base font-bold text-ink">Available Medicines</h2>
                    <span class="text-xs font-semibold text-brand-dark" x-text="results.length + ' in stock'"></span>
                </div>

                <ul class="divide-y divide-mint-2">
                    <template x-for="p in results" :key="p.id">
                        <li class="flex items-center gap-4 px-5 py-4">
                            <img :src="p.photo" :alt="p.name" class="h-12 w-12 shrink-0 rounded-md border border-mint-2 bg-mint-3 object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-display text-base font-semibold text-ink" x-text="p.name"></p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    <span class="pill-green py-0! text-[11px] font-medium" x-text="p.category + (p.subtitle ? ' • ' + p.subtitle : '')"></span>
                                </div>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2 text-sm">
                                    <span class="font-display font-bold text-brand" x-text="peso(p.price)"></span>
                                    <span class="text-xs text-muted" x-text="'/ ' + p.unit"></span>
                                    <template x-if="!p.low"><span class="pill-green py-0! text-[11px]">• <span x-text="p.stock + ' in stock'"></span></span></template>
                                    <template x-if="p.low"><span class="pill-warn py-0! text-[11px]">• Low Stock: <span x-text="p.stock + ' remaining'"></span></span></template>
                                </div>
                            </div>
                            <button type="button" @click="add(p)" :disabled="inCart(p) >= p.stock" class="btn-primary shrink-0 px-6 py-3"><span x-text="inCart(p) >= p.stock ? 'Max' : '+ Add'"></span></button>
                        </li>
                    </template>
                    <li x-show="!results.length" class="px-5 py-14 text-center text-sm text-muted">No sellable products match your search.</li>
                </ul>
            </div>
        </div>

        {{-- ================= RIGHT: CART ================= --}}
        <aside class="card p-6 lg:sticky lg:top-24">
            <div class="flex items-start justify-between border-b border-mint-2 pb-4">
                <div>
                    <h2 class="font-display text-xl font-bold text-ink">Current Sale</h2>
                    <p class="text-sm text-muted">{{ $register }}</p>
                </div>
                <span class="pill-green text-xs" x-text="cart.length + (cart.length === 1 ? ' Item Added' : ' Items Added')"></span>
            </div>

            <div class="divide-y divide-mint-2">
                <template x-for="l in cart" :key="l.id">
                    <div class="py-4">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-display text-[15px] font-semibold text-ink" x-text="l.name"></p>
                            <p class="font-display text-[17px] font-bold text-ink" x-text="peso(l.price * l.qty)"></p>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-sm text-muted" x-text="peso(l.price) + ' each'"></span>
                            <div class="flex items-center rounded-lg border border-line bg-mint-3">
                                <button type="button" @click="dec(l)" class="grid h-8 w-8 place-items-center text-ink hover:bg-mint-2" :aria-label="'Decrease ' + l.name"><x-icon name="minus" class="h-3.5 w-3.5" /></button>
                                <span class="w-8 text-center text-sm font-bold text-ink" x-text="l.qty"></span>
                                <button type="button" @click="inc(l)" :disabled="l.qty >= l.stock" class="grid h-8 w-8 place-items-center text-ink hover:bg-mint-2 disabled:opacity-40" :aria-label="'Increase ' + l.name"><x-icon name="plus" class="h-3.5 w-3.5" /></button>
                            </div>
                        </div>
                    </div>
                </template>
                <p x-show="!cart.length" class="py-10 text-center text-sm text-muted">No items yet. Tap <b>+ Add</b> on a medicine to start the sale.</p>
            </div>

            {{-- Senior / PWD --}}
            <label class="mt-2 flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-mint-2 bg-mint-3 px-3.5 py-3">
                <span class="flex items-center gap-3">
                    <x-icon name="user" class="h-5 w-5 text-brand-dark" />
                    <span class="leading-tight">
                        <span class="block text-sm font-semibold text-ink">Senior Citizen / PWD (20%)</span>
                        <span class="block text-[11px] text-muted">Republic Act 9994 / 10754 Compliant</span>
                    </span>
                </span>
                <input type="checkbox" x-model="senior" class="sr-only">
                <span class="relative h-6 w-11 shrink-0 rounded-full transition" :class="senior ? 'bg-brand' : 'bg-line'">
                    <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all" :class="senior ? 'left-[22px]' : 'left-0.5'"></span>
                </span>
            </label>

            {{-- Totals --}}
            <div class="mt-4 rounded-xl border-2 border-brand bg-mint-2/60 p-4">
                <div class="flex justify-between text-sm"><span class="text-body">Subtotal</span><span class="font-semibold text-ink" x-text="peso(subtotal)"></span></div>
                <div class="mt-1.5 flex justify-between text-sm" x-show="senior" x-transition>
                    <span class="flex items-center gap-1.5 text-brand-dark"><x-icon name="circle-check" class="h-3.5 w-3.5" /> Senior / PWD 20% Discount</span>
                    <span class="font-semibold text-brand-dark" x-text="'-' + peso(discount)"></span>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-brand/20 pt-3">
                    <span class="font-display text-lg font-bold text-ink">Total Due:</span>
                    <span class="font-display text-[28px] font-bold leading-none text-brand-dark" x-text="peso(total)"></span>
                </div>
            </div>

            {{-- Cash --}}
            <div class="mt-5">
                <label for="cash" class="mb-1.5 block text-sm font-bold text-ink">Cash Received</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 font-display text-lg font-bold text-ink">₱</span>
                    <input id="cash" type="number" min="0" step="0.01" inputmode="decimal" x-model="cash" placeholder="0.00" class="input pl-9 pr-24 font-display text-lg font-bold">
                    <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xs font-medium text-muted">Cash Only</span>
                </div>
                <div class="mt-2.5 grid grid-cols-4 gap-2">
                    <button type="button" @click="cash = total.toFixed(2)" :disabled="!cart.length" class="rounded-lg border border-line bg-mint-3 px-1 py-2 text-[11px] font-semibold leading-tight text-ink hover:bg-mint-2 disabled:opacity-50">Exact<br><span x-text="peso(total)"></span></button>
                    <template x-for="n in [200, 500, 1000]" :key="n">
                        <button type="button" @click="cash = n.toFixed(2)" class="rounded-lg border px-1 py-2 text-xs font-bold transition" :class="Number(cash) === n ? 'border-brand bg-brand text-white' : 'border-line bg-mint-3 text-ink hover:bg-mint-2'" x-text="peso(n).replace('.00','')"></button>
                    </template>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between rounded-lg border border-brand/20 bg-mint-2 px-4 py-3.5">
                <span class="font-display text-base font-bold text-ink">Change to Give:</span>
                <span class="font-display text-2xl font-bold" :class="short ? 'text-danger' : 'text-brand-dark'" x-text="short ? 'Insufficient' : peso(change)"></span>
            </div>

            <p x-show="error" x-text="error" class="mt-3 rounded-lg border border-danger-line bg-danger-bg px-3 py-2 text-sm font-medium text-danger" role="alert"></p>

            <button type="button" @click="complete()" :disabled="!canComplete || busy" class="btn-primary mt-4 w-full py-3.5 text-base">
                <x-icon name="circle-check" class="h-5 w-5" /> <span x-text="busy ? 'Processing…' : 'Complete Sale'"></span>
            </button>
        </aside>
    </div>
</div>

@push('scripts')
<script>
window.POS_CONFIG = {
    initial: @json($initial),
    searchUrl: @json(route('sales.search')),
    storeUrl: @json(route('sales.store')),
};

function pos() {
    const { initial, searchUrl, storeUrl } = window.POS_CONFIG;
    const cents = (n) => Math.round(Number(n) * 100);
    return {
        q: '', results: initial, cart: [], senior: false, cash: '', busy: false, error: '',

        async search() {
            try {
                const res = await window.api(searchUrl + '?q=' + encodeURIComponent(this.q.trim()));
                this.results = res;
            } catch (e) { this.error = e.message; }
        },
        inCart(p) { return this.cart.find(l => l.id === p.id)?.qty ?? 0; },
        add(p) {
            this.error = '';
            const line = this.cart.find(l => l.id === p.id);
            if (line) { if (line.qty < p.stock) line.qty++; }
            else this.cart.push({ id: p.id, name: p.name, price: p.price, stock: p.stock, qty: 1 });
        },
        inc(l) { if (l.qty < l.stock) l.qty++; },
        dec(l) { l.qty--; if (l.qty <= 0) this.cart = this.cart.filter(x => x.id !== l.id); },

        get subtotal() { return this.cart.reduce((s, l) => s + cents(l.price) * l.qty, 0) / 100; },
        get discount() { return this.senior ? Math.round(cents(this.subtotal) * 0.2) / 100 : 0; },
        get total()    { return Math.round((cents(this.subtotal) - cents(this.discount))) / 100; },
        get received() { return this.cash === '' ? 0 : Number(this.cash); },
        get short()    { return this.cash !== '' && cents(this.received) < cents(this.total); },
        get change()   { return Math.max(0, cents(this.received) - cents(this.total)) / 100; },
        get canComplete() { return this.cart.length > 0 && this.cash !== '' && !this.short; },

        async complete() {
            if (!this.canComplete) return;
            this.busy = true; this.error = '';
            try {
                const res = await window.api(storeUrl, { method: 'POST', body: {
                    items: this.cart.map(l => ({ product_id: l.id, quantity: l.qty })),
                    senior_pwd: this.senior,
                    cash_received: this.received,
                }});
                window.location.href = res.redirect;
            } catch (e) { this.error = e.message; this.busy = false; }
        },
    };
}
</script>
@endpush
@endsection

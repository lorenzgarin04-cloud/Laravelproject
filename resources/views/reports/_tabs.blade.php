@php $active = $active ?? 'stock'; @endphp
<div class="mt-5 inline-flex rounded-xl border border-mint-2 bg-white p-1 shadow-sm" role="tablist">
    <a href="{{ route('reports.index') }}" role="tab" aria-selected="{{ $active === 'stock' ? 'true' : 'false' }}"
       class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition {{ $active === 'stock' ? 'bg-brand text-white' : 'text-ink hover:bg-mint' }}">
        <x-icon name="file" class="h-4 w-4" /> Sales &amp; Stock
    </a>
    <a href="{{ route('reports.analytics') }}" role="tab" aria-selected="{{ $active === 'analytics' ? 'true' : 'false' }}"
       class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition {{ $active === 'analytics' ? 'bg-brand text-white' : 'text-ink hover:bg-mint' }}">
        <x-icon name="chart" class="h-4 w-4" /> Analytics
    </a>
</div>

@props(['type'])
@php
    $classes = [
        'branded' => 'border-brand/25 bg-mint-2 text-brand-dark',
        'generic' => 'border-line bg-white text-body',
        'grocery' => 'border-warn-line bg-warn-bg text-warn',
    ];
    $label = \App\Models\Product::TYPES[$type] ?? ucfirst((string) $type);
@endphp
<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $classes[$type] ?? $classes['generic'] }}">{{ $label }}</span>

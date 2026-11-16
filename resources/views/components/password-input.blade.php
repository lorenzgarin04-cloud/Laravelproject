@props(['id', 'name', 'placeholder' => '', 'autocomplete' => 'current-password', 'required' => true, 'icon' => true])
<div class="relative">
    @if ($icon)
        <x-icon name="lock" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="password" autocomplete="{{ $autocomplete }}" placeholder="{{ $placeholder }}"
           @if ($required) required @endif
           {{ $attributes->merge(['class' => 'input pr-12 ' . ($icon ? 'input-icon' : '')]) }}>
    <button type="button" onclick="togglePw(this)" aria-label="Show password"
            class="absolute right-3.5 top-1/2 -translate-y-1/2 rounded p-0.5 text-body hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40">
        <span data-eye><x-icon name="eye" class="h-5 w-5" /></span>
        <span data-eye-off class="hidden"><x-icon name="eye-off" class="h-5 w-5" /></span>
    </button>
</div>

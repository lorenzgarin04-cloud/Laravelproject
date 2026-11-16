@props(['class' => 'h-auto w-40'])
@php $src = file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : asset('images/logo.svg'); @endphp
<img src="{{ $src }}" alt="De Guzman Drugmart" {{ $attributes->merge(['class' => $class]) }}>

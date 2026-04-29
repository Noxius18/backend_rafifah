@props([
    'name',
    'placeholder'
])

<input name="{{ $name }}" placeholder="{{ $placeholder }}" {{ $attributes->merge(['class' => 'w-full px-3 py-2']) }}>
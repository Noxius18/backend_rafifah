@props([
    'name',
    'label',
    'type'     => 'text',
    'required' => false,
    'icon'     => null,
    'min'      => null,
    'max'      => null,
])

@php
    $hasError = $errors->has($name);
@endphp

<div class="form-control w-full">
    <label class="label pb-1.5" for="{{ $name }}">
        <span class="label-text font-medium text-slate-700">
            {{ $label }}
            @if ($required)<span class="text-red-500 ml-0.5">*</span>@endif
        </span>
    </label>

    <div class="relative">
        @if ($icon)
            <span class="pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center pl-3 text-slate-400">
                @svg('heroicon-o-' . $icon, 'h-4 w-4')
            </span>
        @endif

        <input
            id="{{ $name }}"
            type="{{ $type }}"
            name="{{ $name }}"
            @if ($required) required @endif
            @if($min) min="{{ $min }}" @endif
            @if($max) max="{{ $max }}" @endif
            {{ $attributes->class([
                'input input-bordered w-full text-sm transition duration-150',
                'input-sm',
                'pl-9' => $icon,
                'input-error border-red-400 focus:border-red-500 focus:ring-red-200' => $hasError,
                'focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100' => !$hasError,
            ]) }}
        />

        @if ($hasError)
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                @svg('heroicon-o-exclamation-circle', 'h-4 w-4 text-red-400')
            </span>
        @endif
    </div>

    @error($name)
        <label class="label pt-1 pb-0">
            <span class="label-text-alt text-red-500 flex items-center gap-1">
                @svg('heroicon-o-exclamation-triangle', 'h-3.5 w-3.5')
                {{ $message }}
            </span>
        </label>
    @enderror
</div>
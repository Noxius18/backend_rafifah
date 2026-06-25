@props([
    'type' => 'info',
    'alert' => null,
])

@php
    $typeConfig = [
        'success' => [
            'class' => 'alert-success border-emerald-200 bg-emerald-50 text-emerald-800',
            'icon' => 'M5 13l4 4L19 7',
            'iconClass' => 'text-emerald-500',
        ],
        'error' => [
            'class' => 'alert-error border-rose-200 bg-rose-50 text-rose-800',
            'icon' => 'M6 18L18 6M6 6l12 12',
            'iconClass' => 'text-rose-500',
        ],
        'warning' => [
            'class' => 'alert-warning border-amber-200 bg-amber-50 text-amber-800',
            'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L4.082 16.126zM12 15.75h.007v.008H12v-.008z',
            'iconClass' => 'text-amber-500',
        ],
        'info' => [
            'class' => 'alert-info border-sky-200 bg-sky-50 text-sky-800',
            'icon' => 'M12 21a9 9 0 100-18 9 9 0 000 18zm0-10.5h.008v.008H12V10.5zm-.75 2.5h1.5v4h-1.5v-4z',
            'iconClass' => 'text-sky-500',
        ],
    ];

    $config = $typeConfig[$type] ?? $typeConfig['info'];
@endphp

<div role="alert" {{ $attributes->merge(['class' => 'alert rounded-xl border px-4 py-3 shadow-sm ' . $config['class']]) }}>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 {{ $config['iconClass'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $config['icon'] }}" />
    </svg>

    <div class="min-w-0 flex-1">
        @if(!is_null($alert) && $alert !== '')
            <div class="text-sm font-medium leading-6">{{ $alert }}</div>
        @endif

        @if($slot->isNotEmpty())
            <div class="mt-2 text-sm leading-6">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>

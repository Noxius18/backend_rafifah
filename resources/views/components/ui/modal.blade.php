@props([
    'id' => 'modal',
    'size' => 'md', // sm, md, lg, xl
    'closeOnBackdrop' => true,
])

@php
    $sizeClass = match($size) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        default => 'max-w-md',
    };
@endphp

{{ $trigger ?? '' }}

<dialog id="{{ $id }}" class="modal" {{ $closeOnBackdrop ? 'onclick="if(event.target === this) this.close();"' : '' }}>
    <div class="modal-box {{ $sizeClass }} p-0 overflow-hidden rounded-2xl shadow-xl">
        {{-- Header --}}
        @if($header ?? false)
            <div class="border-b border-slate-100 px-6 py-4">
                {{ $header }}
            </div>
        @endif

        {{-- Body --}}
        <div class="px-6 py-5 overflow-y-auto overflow-x-hidden max-h-[75vh] break-words">
            {{ $body ?? $slot }}
        </div>

        {{-- Footer --}}
        @if($footer ?? false)
            <div class="border-t border-slate-100 bg-slate-50/50 px-6 py-3.5 flex items-center justify-end gap-2">
                {{ $footer }}
            </div>
        @endif
    </div>

    {{-- Backdrop with blur --}}
    <form method="dialog" class="modal-backdrop backdrop-blur-sm bg-black/20">
        <button>close</button>
    </form>
</dialog>
{{--
    Generic modal untuk form. Submit lewat HTML form biasa → Laravel redirect.
    Alpine hanya untuk buka/tutup modal dan tampilkan error validasi dari $errors.

    USAGE:
    <x-ui.modal-form id="addModal" title="Tambah Data" open-event="openAddModal">
        <x-slot name="body">
            <form id="addModal-form" action="{{ route('...') }}" method="POST">
                @csrf
                ... fields ...
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" onclick="addModal.close()">Batal</button>
            <button type="submit" form="addModal-form">Simpan</button>
        </x-slot>
    </x-ui.modal-form>

    Props:
    - id         → id elemen <dialog> DaisyUI
    - title      → teks header modal
    - size       → ukuran modal, diteruskan ke x-ui.modal (default: 'md')
    - open-event → nama CustomEvent yang membuka modal ini (opsional)
                   berguna agar tombol di luar scope Alpine bisa memicu modal
                   contoh: open-event="openAddModal" → listen ke event 'openAddModal'
--}}

@props([
    'id',
    'title',
    'subtitle'  => null,
    'icon'      => null,
    'size'      => 'md',
    'openEvent' => null,
])

@php
    $iconPaths = [
        'plus'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'edit'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />',
        'user'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />',
        'delete'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />',
    ];
@endphp

<x-ui.modal :id="$id" :size="$size">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            @if ($icon && isset($iconPaths[$icon]))
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        {!! $iconPaths[$icon] !!}
                    </svg>
                </div>
            @else
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
            @endif
            <div>
                <h3 class="text-lg font-semibold text-slate-800">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
    </x-slot>

    <x-slot name="body">
        {{ $body }}
    </x-slot>

    <x-slot name="footer">
        {{ $footer }}
    </x-slot>
</x-ui.modal>

@if ($openEvent)
<script>
    document.addEventListener('{{ $openEvent }}', () => {
        document.getElementById('{{ $id }}').showModal();
    });
</script>
@endif
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
    'size'      => 'md',
    'openEvent' => null,
])

<x-ui.modal :id="$id" :size="$size">
    <x-slot name="header">
        <h3 class="text-lg font-semibold text-slate-800">{{ $title }}</h3>
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
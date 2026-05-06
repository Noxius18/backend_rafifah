{{--
    Generic modal konfirmasi untuk aksi destruktif (hapus, arsip, dll).
    Submit lewat hidden form biasa → Laravel redirect. Tidak ada JS fetch.

    USAGE:
    <x-ui.modal-confirm
        id="deleteModal"
        title="Konfirmasi Hapus"
        body-text="Apakah Anda yakin ingin menghapus"
        form-action="/panitia"
        form-method="DELETE"
    />

    Buka modal dari tombol di tabel (inline atau via Alpine):
    <button onclick="openConfirmModal('/panitia/' + id, 'Nama Panitia')">Hapus</button>

    Sertakan helper JS ini sekali di layout/page (sudah include otomatis via komponen ini):
    function openConfirmModal(action, name) {
        document.getElementById('{{ $id }}-name').textContent = name;
        document.getElementById('{{ $id }}-form').action = action;
        document.getElementById('{{ $id }}').showModal();
    }

    Props:
    - id           → id elemen <dialog>                    (default: 'confirmModal')
    - title        → judul header modal                    (default: 'Konfirmasi')
    - subtitle     → subjudul header                       (default: 'Tindakan ini tidak dapat dibatalkan')
    - bodyText     → kalimat sebelum nama target           (default: 'Apakah Anda yakin...')
    - confirmLabel → teks tombol konfirmasi                (default: 'Hapus')
    - confirmClass → kelas tambahan tombol konfirmasi      (default: 'btn-error')
    - cancelLabel  → teks tombol batal                     (default: 'Batal')
--}}

@props([
    'id'           => 'confirmModal',
    'title'        => 'Konfirmasi',
    'subtitle'     => 'Tindakan ini tidak dapat dibatalkan',
    'bodyText'     => 'Apakah Anda yakin ingin menghapus',
    'confirmLabel' => 'Hapus',
    'confirmClass' => 'btn-error',
    'cancelLabel'  => 'Batal',
])

{{-- Hidden form — action diisi dinamis oleh openConfirmModal() --}}
<form id="{{ $id }}-form" method="POST" action="">
    @csrf
    @method('DELETE')
</form>

<x-ui.modal :id="$id" size="sm">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-slate-800">{{ $title }}</h3>
                <p class="text-xs text-slate-500">{{ $subtitle }}</p>
            </div>
        </div>
    </x-slot>

    <x-slot name="body">
        <p class="text-sm text-slate-600">
            {{ $bodyText }}
            <strong class="text-slate-800" id="{{ $id }}-name"></strong>?
        </p>
    </x-slot>

    <x-slot name="footer">
        <button type="button" class="btn btn-ghost btn-sm"
            onclick="document.getElementById('{{ $id }}').close()">
            {{ $cancelLabel }}
        </button>
        <button type="submit" form="{{ $id }}-form" class="btn btn-sm {{ $confirmClass }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            {{ $confirmLabel }}
        </button>
    </x-slot>
</x-ui.modal>

{{-- Helper: isi action form + nama target sebelum modal dibuka --}}
<script>
    function openConfirmModal(action, name) {
        document.getElementById('{{ $id }}-name').textContent = name;
        document.getElementById('{{ $id }}-form').action = action;
        document.getElementById('{{ $id }}').showModal();
    }
</script>
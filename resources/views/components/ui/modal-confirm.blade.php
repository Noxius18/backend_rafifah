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
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-slate-800">{{ $title }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
            </div>
        </div>
    </x-slot>

    <x-slot name="body">
        <div class="space-y-1">
            <p class="text-sm text-slate-600 leading-relaxed">
                {{ $bodyText }}
                <strong class="text-slate-800 font-semibold" id="{{ $id }}-name"></strong>?
            </p>
        </div>
    </x-slot>

    <x-slot name="footer">
        <button type="button" class="btn btn-ghost btn-sm text-slate-500 hover:text-slate-700 hover:bg-slate-100"
            onclick="document.getElementById('{{ $id }}').close()">
            {{ $cancelLabel }}
        </button>
        <button type="submit" form="{{ $id }}-form" class="btn btn-sm {{ $confirmClass }} text-white gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
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
@extends('layouts.app')

@section('content')

@php
    $jabatanOptions = ['Pengawas' => 'Pengawas', 'Panitia' => 'Panitia', 'Penguji' => 'Penguji'];

    // ── HTML renderers untuk kolom tabel ───────────────────────────────────
    $columns = [
        ['label' => 'ID',       'field' => 'id_panitia',   'html' => 'id_html'],
        ['label' => 'Nama',     'field' => 'nama_lengkap', 'html' => 'nama_html'],
        ['label' => 'Username', 'field' => 'username',     'html' => 'username_html'],
        ['label' => 'No. HP',   'field' => 'no_hp'],
        ['label' => 'Jabatan',  'field' => 'jabatan',      'html' => 'jabatan_html'],
        ['label' => 'Aksi',     'field' => 'id_panitia',   'html' => 'aksi_html', 'class' => 'text-right'],
    ];

    $rows = $panitias->map(fn($p) => [
        // Plain — untuk sort
        'id_panitia'    => $p->id_panitia,
        'nama_lengkap'  => $p->nama_lengkap,
        'username'      => $p->username,
        'no_hp'         => $p->no_hp,
        'jabatan'       => $p->jabatan,

        // HTML — untuk render
        'id_html'       => "<code class='rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500'>{$p->id_panitia}</code>",
        'nama_html'     => "<div class='flex items-center gap-2.5'>
                                <div class='flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-bold text-emerald-700'>" . strtoupper(substr($p->nama_lengkap, 0, 1)) . "</div>
                                <span class='font-medium text-slate-700'>" . e($p->nama_lengkap) . "</span>
                            </div>",
        'username_html' => "<span class='font-mono text-xs text-slate-500'>" . e($p->username) . "</span>",
        'jabatan_html'  => match($p->jabatan) {
            'Penguji'  => "<span class='rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200'>Penguji</span>",
            'Panitia'  => "<span class='rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200'>Panitia</span>",
            'Pengawas' => "<span class='rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-sky-200'>Pengawas</span>",
            default    => "<span class='text-slate-400'>" . e($p->jabatan) . "</span>",
        },
        // Tombol edit membuka modal + isi form via data attribute & JS ringkas
        // Tombol hapus memanggil openConfirmModal() dari x-ui.modal-confirm
        'aksi_html'     => "<div class='flex items-center justify-end gap-1'>
                                <button type='button'
                                    onclick=\"openEditModal({
                                        id:       '{$p->id_panitia}',
                                        nama:     '" . e($p->nama_lengkap) . "',
                                        username: '" . e($p->username) . "',
                                        no_hp:    '" . e($p->no_hp) . "',
                                        jabatan:  '" . e($p->jabatan) . "'
                                    })\"
                                    class='rounded-md px-2.5 py-1 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700'>Edit</button>
                                <span class='text-slate-200'>|</span>
                                <button type='button'
                                    onclick=\"openConfirmModal('/panitia/{$p->id_panitia}', '" . e($p->nama_lengkap) . "')\"
                                    class='rounded-md px-2.5 py-1 text-xs font-medium text-slate-500 transition hover:bg-rose-50 hover:text-rose-600'>Hapus</button>
                            </div>",

        // Search index
        'search' => strtolower("{$p->id_panitia} {$p->nama_lengkap} {$p->username} {$p->no_hp} {$p->jabatan}"),
    ])->toArray();
@endphp

{{--
    x-init: munculkan toast dari session flash Laravel setelah redirect.
    Tidak ada CRUD di Alpine — semua sudah dihandle controller.
--}}
<div x-data="{
    toast: { show: false, message: '', type: 'success', timer: null },
    showToast(message, type = 'success') {
        if (this.toast.timer) clearTimeout(this.toast.timer);
        Object.assign(this.toast, { message, type, show: true });
        this.toast.timer = setTimeout(() => this.toast.show = false, 4000);
    },
}"
x-init="
    @if(session('success')) showToast('{{ session('success') }}') @endif
    @if(session('error'))   showToast('{{ session('error') }}', 'error') @endif
">

    <x-ui.toast />

    <x-ui.sidebar>
        <section class="space-y-6 px-1 py-2">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-1 h-7 w-1 rounded-full bg-emerald-500"></div>
                    <div>
                        <h1 class="text-xl font-semibold text-slate-800">Daftar Panitia</h1>
                        <p class="text-sm text-slate-400">Kelola data panitia, pengawas, dan penguji.</p>
                    </div>
                </div>
                <button type="button" onclick="addModal.showModal()"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95">
                    <x-heroicon-s-user-plus class="h-4 w-4" />
                    Tambah
                </button>
            </div>

            {{-- Table --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <x-ui.data-table
                    :rows="$rows"
                    :columns="$columns"
                    :total="$panitias->count()"
                    empty-message="Belum ada data panitia"
                    empty-sub="Mulai dengan menambahkan panitia pertama."
                    add-label="Tambah Panitia"
                />
            </div>

        </section>
    </x-ui.sidebar>

    {{-- ── Modal: Tambah ──────────────────────────────────────────────── --}}
    <x-ui.modal-form id="addModal" title="Tambah Panitia Baru">
        <x-slot name="body">
            <form id="addModal-form" action="{{ route('panitia.store') }}" method="POST" class="space-y-3">
                @csrf
                <x-ui.form-input name="nama_lengkap" label="Nama Lengkap" placeholder="Masukkan nama lengkap" maxlength="30" required />
                <x-ui.form-input name="username"     label="Username"     placeholder="Masukkan username"     maxlength="10" required />
                <x-ui.form-input name="no_hp"        label="No. HP"       placeholder="Contoh: 081234567890"  maxlength="13" type="tel" required />
                <x-ui.form-input name="password"     label="Password"     placeholder="Minimal 6 karakter"    type="password" required />
                <x-ui.form-select name="jabatan" label="Jabatan" :options="$jabatanOptions" required />
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="addModal.close()">Batal</button>
            <button type="submit" form="addModal-form" class="btn btn-success btn-sm">Simpan</button>
        </x-slot>
    </x-ui.modal-form>

    {{-- ── Modal: Edit ────────────────────────────────────────────────── --}}
    <x-ui.modal-form id="editModal" title="Edit Panitia">
        <x-slot name="body">
            <form id="editModal-form" action="" method="POST" class="space-y-3">
                @csrf
                @method('PUT')

                {{-- ID readonly --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold text-sm">ID Panitia</span>
                    </label>
                    <input type="text" id="editModal-id-display" class="input input-bordered input-sm bg-base-200" disabled />
                    <label class="label">
                        <span class="label-text-alt text-base-content/50">ID tidak dapat diubah</span>
                    </label>
                </div>

                <x-ui.form-input name="nama_lengkap" label="Nama Lengkap" placeholder="Masukkan nama lengkap" maxlength="30" required />
                <x-ui.form-input name="username"     label="Username"     placeholder="Masukkan username"     maxlength="10" required />
                <x-ui.form-input name="no_hp"        label="No. HP"       placeholder="Contoh: 081234567890"  maxlength="13" type="tel" required />
                <x-ui.form-input name="password"     label="Password"     placeholder="Biarkan kosong jika tidak ingin mengubah" type="password" />
                <x-ui.form-select name="jabatan" label="Jabatan" :options="$jabatanOptions" required />
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="editModal.close()">Batal</button>
            <button type="submit" form="editModal-form" class="btn btn-success btn-sm">Perbarui</button>
        </x-slot>
    </x-ui.modal-form>

    {{-- ── Modal: Hapus ───────────────────────────────────────────────── --}}
    <x-ui.modal-confirm
        id="deleteModal"
        title="Konfirmasi Hapus"
        body-text="Apakah Anda yakin ingin menghapus data panitia"
        confirm-label="Hapus"
    />

</div>

{{--
    JS minimal — hanya mengisi field form edit sebelum modal dibuka.
    Tidak ada fetch, tidak ada state management CRUD.
--}}
<script>
    function openEditModal({ id, nama, username, no_hp, jabatan }) {
        // Isi action form dengan route yang benar
        document.getElementById('editModal-form').action = `/panitia/${id}`;

        // Isi field-field form
        document.getElementById('editModal-id-display').value  = id;
        document.querySelector('#editModal-form [name="nama_lengkap"]').value = nama;
        document.querySelector('#editModal-form [name="username"]').value     = username;
        document.querySelector('#editModal-form [name="no_hp"]').value        = no_hp;
        document.querySelector('#editModal-form [name="jabatan"]').value      = jabatan;

        // Reset password (tidak pre-fill)
        document.querySelector('#editModal-form [name="password"]').value     = '';

        editModal.showModal();
    }
</script>

@endsection
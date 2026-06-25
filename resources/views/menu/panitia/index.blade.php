@extends('layouts.app')

@section('content')

@php
    $jabatanOptions = ['Ketua Panitia' => 'Ketua Panitia', 'Panitia' => 'Panitia'];

    $columns = [
        ['label' => 'ID',       'field' => 'id_panitia',   'html' => 'id_html'],
        ['label' => 'Nama',     'field' => 'nama_lengkap', 'html' => 'nama_html'],
        ['label' => 'Username', 'field' => 'username',     'html' => 'username_html'],
        ['label' => 'No. HP',   'field' => 'no_hp'],
        ['label' => 'Jabatan',  'field' => 'jabatan',      'html' => 'jabatan_html'],
        ['label' => 'Aksi',     'field' => 'id_panitia',   'html' => 'aksi_html', 'class' => 'text-right'],
    ];

    $rows = $panitias->map(function($p) {
        $isMe = auth()->user()->id_panitia === $p->id_panitia;

        // Render tombol hapus. Kalau ini diri sendiri, tombolnya jadi disable dan warna abu-abu.
        $deleteBtn = $isMe 
            ? "<button type='button' disabled class='inline-flex items-center justify-center rounded-md p-2 text-slate-300 cursor-not-allowed' title='Tidak bisa hapus akun yang sedang digunakan'>
                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0'/></svg>
               </button>"
            : "<button type='button' onclick=\"openConfirmModal('/panitia/{$p->id_panitia}', '" . e($p->nama_lengkap) . "')\" class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-rose-600 hover:bg-rose-50' title='Hapus'>
                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0'/></svg>
               </button>";

        return [
            'id_panitia'    => $p->id_panitia,
            'nama_lengkap'  => $p->nama_lengkap,
            'username'      => $p->username,
            'no_hp'         => $p->no_hp,
            'jabatan'       => $p->jabatan,

            'id_html'       => "<code class='rounded bg-black/[0.05] px-1.5 py-0.5 text-xs text-black'>{$p->id_panitia}</code>",
            'nama_html'     => "<span class='font-medium text-black'>" . e($p->nama_lengkap) . "</span>",
            'username_html' => "<span class='font-mono text-xs text-black/60'>" . e($p->username) . "</span>",
            'jabatan_html'  => match($p->jabatan) {
                'Penguji'  => "<span class='rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200'>Penguji</span>",
                'Panitia'  => "<span class='rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200'>Panitia</span>",
                'Ketua Panitia' => "<span class='rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-sky-200'>Ketua Panitia</span>",
                default    => "<span class='text-black'>" . e($p->jabatan) . "</span>",
            },
            'aksi_html'     => "<div class='flex items-center justify-end gap-0.5'>
                                    <button type='button'
                                        onclick=\"openEditModal({ id: '{$p->id_panitia}', nama: '" . e($p->nama_lengkap) . "', username: '" . e($p->username) . "', no_hp: '" . e($p->no_hp) . "', jabatan: '" . e($p->jabatan) . "' })\"
                                        class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-emerald-600 hover:bg-emerald-50' title='Edit'>
                                        <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10'/></svg>
                                    </button>
                                    {$deleteBtn}
                                </div>",
            'search' => strtolower("{$p->id_panitia} {$p->nama_lengkap} {$p->username} {$p->no_hp} {$p->jabatan}"),
        ];
    })->toArray();
@endphp

<div>
    <x-ui.sidebar>
        <section class="space-y-6 px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-1 h-7 w-1 rounded-full bg-emerald-500"></div>
                    <div>
                        <h1 class="text-xl font-semibold text-black">Daftar Panitia</h1>
                        <p class="text-sm text-black">Kelola data panitia, pengawas, dan penguji.</p>
                    </div>
                </div>
                <button type="button" onclick="addModal.showModal()" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95"><x-heroicon-s-user-plus class="h-4 w-4" /> Tambah</button>
            </div>

            <div class="space-y-3">
                @if(session('success'))
                    <x-ui.alert type="success" :alert="session('success')" />
                @endif

                @if(session('error'))
                    <x-ui.alert type="error" :alert="session('error')" />
                @endif
            </div>

            <div class="overflow-hidden rounded-xl border border-black/20 bg-white">
                <x-ui.data-table :rows="$rows" :columns="$columns" :total="$panitias->count()" empty-message="Belum ada data panitia" add-label="Tambah Panitia" />
            </div>
        </section>
    </x-ui.sidebar>

    {{-- MODAL TAMBAH --}}
    <x-ui.modal-form id="addModal" title="Tambah Panitia Baru" subtitle="Lengkapi data panitia baru" icon="plus" size="md">
        <x-slot name="body">
            @if($errors->any() && old('_form') === 'add-panitia')
                <x-ui.alert type="error" alert="Periksa kembali data panitia baru.">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif
            <div id="addModal-client-alert" class="hidden">
                <x-ui.alert type="error" alert="Lengkapi semua field wajib terlebih dahulu." />
            </div>
            <form id="addModal-form" action="{{ route('panitia.store') }}" method="POST" class="space-y-5" novalidate>
                @csrf
                <input type="hidden" name="_form" value="add-panitia">
                <div><x-ui.form-input name="nama_lengkap" label="Nama Lengkap" placeholder="Masukkan nama lengkap" maxlength="30" icon="user" required value="{{ old('nama_lengkap') }}" /></div>
                <div><x-ui.form-input name="username" label="Username" placeholder="Masukkan username" maxlength="10" icon="user" required value="{{ old('username') }}" /></div>
                <div><x-ui.form-input name="no_hp" label="No. HP" placeholder="Contoh: 081234567890" maxlength="13" type="tel" icon="phone" required value="{{ old('no_hp') }}" /></div>
                <div><x-ui.form-input name="password" label="Password" placeholder="Minimal 8 karakter" type="password" icon="lock-closed" required /></div>
                <div data-password-confirm-wrap hidden>
                    <x-ui.form-input name="password_confirmation" label="Konfirmasi Password" placeholder="Ulangi password" type="password" icon="key" />
                    <div data-password-mismatch class="mt-0.5 text-xs text-red-600" hidden>Password dan konfirmasi tidak cocok.</div>
                </div>
                <div><x-ui.form-select name="jabatan" label="Jabatan" :options="$jabatanOptions" icon="user" required :selected="old('jabatan')" /></div>
            </form>
        </x-slot>
        <x-slot name="footer"><button type="button" class="btn btn-ghost btn-sm text-black hover:bg-black/[0.05]" onclick="addModal.close()">Batal</button><button type="submit" form="addModal-form" class="btn btn-sm bg-emerald-600 text-white hover:bg-emerald-700 border-none gap-1.5">Simpan</button></x-slot>
    </x-ui.modal-form>

    {{-- MODAL EDIT --}}
    <x-ui.modal-form id="editModal" title="Edit Panitia" subtitle="Ubah data panitia" icon="edit" size="lg">
        <x-slot name="body">
            <div class="max-h-[65vh] overflow-y-auto -mr-2 pr-2">
            @if($errors->any() && old('_form') === 'edit-panitia')
                <x-ui.alert type="error" alert="Periksa kembali data panitia.">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif
            <form id="editModal-form" action="" method="POST" class="space-y-5" novalidate>
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="edit-panitia">
                <div class="form-control">
                    <label class="label pb-1.5"><span class="label-text font-medium text-slate-700">ID Panitia</span></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /></svg></span>
                        <input type="text" id="editModal-id-display" class="input input-bordered w-full input-sm pl-9 bg-slate-50 text-slate-500 cursor-not-allowed" disabled />
                    </div>
                </div>
            
                <div class="opacity-70 bg-slate-50 pointer-events-none">
                    <x-ui.form-input name="nama_lengkap" label="Nama Lengkap" placeholder="Masukkan nama lengkap" maxlength="30" icon="user" readonly value="{{ old('nama_lengkap') }}" />
                </div>
                <div class="opacity-70 bg-slate-50 pointer-events-none">
                    <x-ui.form-input name="username" label="Username" placeholder="Masukkan username" maxlength="10" icon="user" readonly value="{{ old('username') }}" />
                </div>
                
                <x-ui.form-input name="no_hp" label="No. HP" placeholder="Contoh: 081234567890" maxlength="13" type="tel" icon="phone" value="{{ old('no_hp') }}" />
                <x-ui.form-input name="password" label="Password Baru" placeholder="Biarkan kosong jika tidak ingin mengubah" type="password" icon="key" />
                
                <div data-password-confirm-wrap hidden>
                    <x-ui.form-input name="password_confirmation" label="Konfirmasi Password" placeholder="Ulangi password" type="password" icon="key" />
                </div>
                <div data-password-mismatch class="text-sm text-red-600 mt-1" hidden>Password dan konfirmasi tidak cocok.</div>
            
                {{-- KINI BISA DI-EDIT --}}
                <x-ui.form-select name="jabatan" label="Jabatan" :options="$jabatanOptions" icon="briefcase" :selected="old('jabatan')" />
            </form>
            </div>
        </x-slot>
        <x-slot name="footer"><button type="button" class="btn btn-ghost btn-sm text-black hover:bg-black/[0.05]" onclick="editModal.close()">Batal</button><button type="submit" form="editModal-form" class="btn btn-sm bg-emerald-600 text-white hover:bg-emerald-700 border-none gap-1.5">Perbarui</button></x-slot>
    </x-ui.modal-form>

    <x-ui.modal-confirm id="deleteModal" title="Konfirmasi Hapus" body-text="Apakah Anda yakin ingin menghapus data panitia" confirm-label="Hapus" />
</div>

<script>
    function bindRequiredFieldsAlert(formId, alertId, message) {
        const form = document.getElementById(formId);
        const alert = document.getElementById(alertId);
        if (!form || !alert) return;

        const getRequiredFields = () => Array.from(form.querySelectorAll('[required]')).filter((field) => {
            if (field.disabled || field.type === 'hidden') return false;
            return !field.closest('[hidden]');
        });

        const isEmpty = (field) => {
            if (field.type === 'file') return field.files.length === 0;
            return !String(field.value ?? '').trim();
        };

        const toggleAlert = (show, text = message) => {
            alert.classList.toggle('hidden', !show);
            const textNode = alert.querySelector('[role="alert"] .text-sm');
            if (textNode) {
                textNode.textContent = text;
            }
        };

        form.addEventListener('submit', function (event) {
            const firstEmptyField = getRequiredFields().find(isEmpty);
            if (!firstEmptyField) {
                toggleAlert(false);
                return;
            }

            event.preventDefault();
            toggleAlert(true);
            firstEmptyField.focus();
        });

        form.addEventListener('input', function () {
            if (!getRequiredFields().some(isEmpty)) {
                toggleAlert(false);
            }
        });

        form.addEventListener('change', function () {
            if (!getRequiredFields().some(isEmpty)) {
                toggleAlert(false);
            }
        });
    }

    function bindPasswordConfirmation(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        const passwordInput = form.querySelector('[name="password"]');
        const confirmInput = form.querySelector('[name="password_confirmation"]');
        const confirmWrap = form.querySelector('[data-password-confirm-wrap]');
        const mismatch = form.querySelector('[data-password-mismatch]');

        if (!passwordInput || !confirmInput || !confirmWrap) return;

        const sync = () => {
            const hasPassword = passwordInput.value.length > 0;
            confirmWrap.hidden = !hasPassword;
            confirmInput.required = hasPassword;

            if (!hasPassword) {
                confirmInput.value = '';
                if (mismatch) mismatch.hidden = true;
                return;
            }

            const hasMismatch = confirmInput.value.length > 0 && passwordInput.value !== confirmInput.value;
            if (mismatch) mismatch.hidden = !hasMismatch;
        };

        passwordInput.addEventListener('input', sync);
        confirmInput.addEventListener('input', sync);
        form.addEventListener('submit', function (event) {
            sync();

            if (passwordInput.value && passwordInput.value !== confirmInput.value) {
                event.preventDefault();
                if (mismatch) mismatch.hidden = false;
                window.alert('Password dan konfirmasi tidak cocok.');
                confirmInput.focus();
            }
        });

        sync();
    }

    function openEditModal({ id, nama, username, no_hp, jabatan }) {
        const form = document.getElementById('editModal-form');
        const passwordInput = form.querySelector('[name="password"]');
        const confirmInput = form.querySelector('[name="password_confirmation"]');

        form.action = `/panitia/${id}`;
        document.getElementById('editModal-id-display').value  = id;
        form.querySelector('[name="nama_lengkap"]').value = nama;
        form.querySelector('[name="username"]').value     = username;
        form.querySelector('[name="no_hp"]').value        = no_hp;
        form.querySelector('[name="jabatan"]').value      = jabatan;
        passwordInput.value = '';
        confirmInput.value = '';
        passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
        editModal.showModal();
    }
    
    function openConfirmModal(url, nama) {
        document.getElementById('deleteModal-name').textContent = nama;
        let form = document.getElementById('deleteModal-form');
        form.action = url;
        document.getElementById('deleteModal').showModal();
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindRequiredFieldsAlert('addModal-form', 'addModal-client-alert', 'Lengkapi semua field wajib terlebih dahulu.');
        bindPasswordConfirmation('addModal-form');
        bindPasswordConfirmation('editModal-form');

        const modalMap = {
            'add-panitia': 'addModal',
            'edit-panitia': 'editModal',
        };

        const modalId = modalMap[@json(old('_form'))];
        if (modalId) {
            document.getElementById(modalId)?.showModal();
        }
    });
</script>

@endsection

@extends('layouts.app')

@section('content')
<div x-data="panitiaManager()">
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
            <a href="#"
                @click.prevent="openAddModal()"
                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Tambah
            </a>
        </div>

        {{-- Alert --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show"
                x-transition:leave="transition duration-200 ease-in"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-700">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ session('success') }}
                </div>
                <button @click="show = false" class="text-emerald-400 hover:text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        {{-- Table Card --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">

            @php
                // Closure HTML untuk tiap kolom
                $jabatanBadge = fn($val) => match($val) {
                    'Penguji'  => "<span class='rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200'>Penguji</span>",
                    'Panitia'  => "<span class='rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200'>Panitia</span>",
                    'Pengawas' => "<span class='rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-sky-200'>Pengawas</span>",
                    default    => "<span class='text-slate-400'>" . e($val) . "</span>",
                };

                $namaHtml = fn($val) =>
                    "<div class='flex items-center gap-2.5'>
                        <div class='flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-bold text-emerald-700'>"
                            . strtoupper(substr($val, 0, 1)) .
                        "</div>
                        <span class='font-medium text-slate-700'>" . e($val) . "</span>
                    </div>";

                $idHtml       = fn($val) => "<code class='rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500'>" . e($val) . "</code>";
                $usernameHtml = fn($val) => "<span class='font-mono text-xs text-slate-500'>" . e($val) . "</span>";
                $aksiHtml     = fn($val, $nama) =>
                    "<div class='flex items-center justify-end gap-1'>
                        <button onclick=\"document.dispatchEvent(new CustomEvent('openEditModal', {detail: {id: '" . e($val) . "'}}))\"
                            class='rounded-md px-2.5 py-1 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700'>Edit</button>
                        <span class='text-slate-200'>|</span>
                        <button onclick=\"openDeleteModal('" . e($val) . "', '" . e($nama) . "')\"
                            class='rounded-md px-2.5 py-1 text-xs font-medium text-slate-500 transition hover:bg-rose-50 hover:text-rose-600'>Hapus</button>
                    </div>";

                // $rows berisi dua jenis field per kolom:
                // - nilai plain  → untuk search & sort (mis. 'jabatan' => 'Penguji')
                // - nilai html   → untuk ditampilkan di tabel (mis. 'jabatan_html')
                $rows = $panitias->map(fn($p) => [
                    // Plain values — dipakai untuk search & sort
                    'id_panitia'   => $p->id_panitia,
                    'nama_lengkap' => $p->nama_lengkap,
                    'username'     => $p->username,
                    'no_hp'        => $p->no_hp,
                    'jabatan'      => $p->jabatan,

                    // HTML values — ditampilkan via x-html di template
                    'id_html'       => $idHtml($p->id_panitia),
                    'nama_html'     => $namaHtml($p->nama_lengkap),
                    'username_html' => $usernameHtml($p->username),
                    'jabatan_html'  => $jabatanBadge($p->jabatan),
                    'aksi_html'     => $aksiHtml($p->id_panitia, $p->nama_lengkap),

                    // Search index — satu string gabungan semua field untuk filter cepat
                    'search' => strtolower(implode(' ', [$p->id_panitia, $p->nama_lengkap, $p->username, $p->no_hp, $p->jabatan])),
                ])->toArray();
            @endphp

            @if($panitias->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-600">Belum ada data panitia</p>
                    <p class="mt-1 text-xs text-slate-400">Mulai dengan menambahkan panitia pertama.</p>
                    <a href="{{ route('panitia.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Tambah Panitia
                    </a>
                </div>

            @else
                <div x-data="panitiaTable()">

                    {{-- Toolbar --}}
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="relative w-full sm:max-w-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text"
                                placeholder="Cari panitia..."
                                x-model="searchTerm"
                                @input="filterAndSort()"
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-sm text-slate-700 placeholder-slate-400 outline-none transition focus:border-emerald-400 focus:bg-white focus:ring-2 focus:ring-emerald-100"
                            />
                        </div>
                        <span class="text-xs text-slate-400">
                            <span class="font-medium text-slate-600" x-text="filteredRows.length"></span>
                            dari {{ $panitias->count() }} data
                        </span>
                    </div>

                    {{-- Table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-slate-100 text-left">
                                <tr class="text-xs font-medium text-slate-400">
                                    {{-- Setiap th bisa diklik untuk sort, pakai field plain value --}}
                                    @foreach([
                                        ['label' => 'ID',       'field' => 'id_panitia'],
                                        ['label' => 'Nama',     'field' => 'nama_lengkap'],
                                        ['label' => 'Username', 'field' => 'username'],
                                        ['label' => 'No. HP',   'field' => 'no_hp'],
                                        ['label' => 'Jabatan',  'field' => 'jabatan'],
                                    ] as $col)
                                        <th @click="toggleSort('{{ $col['field'] }}')"
                                            class="cursor-pointer select-none px-4 py-3 hover:text-slate-600">
                                            <div class="flex items-center gap-1">
                                                {{ $col['label'] }}
                                                <span x-show="sortColumn === '{{ $col['field'] }}'"
                                                      x-text="sortDirection === 'asc' ? '↑' : '↓'"
                                                      class="text-emerald-500"></span>
                                            </div>
                                        </th>
                                    @endforeach
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <template x-for="row in filteredRows" :key="row.id_panitia">
                                    <tr class="hover:bg-slate-50/70">
                                        {{-- Gunakan *_html untuk tampilan, bukan plain value --}}
                                        <td class="px-4 py-3"            x-html="row.id_html"></td>
                                        <td class="px-4 py-3"            x-html="row.nama_html"></td>
                                        <td class="px-4 py-3"            x-html="row.username_html"></td>
                                        <td class="px-4 py-3 text-slate-500" x-text="row.no_hp"></td>
                                        <td class="px-4 py-3"            x-html="row.jabatan_html"></td>
                                        <td class="px-4 py-3"            x-html="row.aksi_html"></td>
                                    </tr>
                                </template>
                                <tr x-show="filteredRows.length === 0" x-cloak>
                                    <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">
                                        Tidak ada hasil untuk
                                        "<span class="text-slate-600" x-text="searchTerm"></span>"
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer --}}
                    <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-400">
                        Menampilkan
                        <span class="font-medium text-slate-600" x-text="filteredRows.length"></span>
                        dari {{ $panitias->count() }} panitia
                    </div>

                </div>
            @endif
        </div>

    </section>
</x-ui.sidebar>

{{-- Add Panitia Modal --}}
<x-ui.modal id="addModal" size="md">
    <x-slot name="header">
        <h3 class="text-lg font-semibold text-slate-800">Tambah Panitia Baru</h3>
    </x-slot>

    <x-slot name="body">
        <form id="addForm" class="space-y-3">
            @csrf
            
            <!-- Nama Lengkap -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">Nama Lengkap <span class="text-red-500">*</span></span>
                </label>
                <input 
                    type="text" 
                    name="nama_lengkap"
                    maxlength="30"
                    placeholder="Masukkan nama lengkap"
                    class="input input-bordered input-sm"
                    required
                />
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.nama_lengkap ?? ''"></span>
            </div>

            <!-- Username -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">Username <span class="text-red-500">*</span></span>
                </label>
                <input 
                    type="text" 
                    name="username"
                    maxlength="10"
                    placeholder="Masukkan username"
                    class="input input-bordered input-sm"
                    required
                />
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.username ?? ''"></span>
            </div>

            <!-- No HP -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">No. HP <span class="text-red-500">*</span></span>
                </label>
                <input 
                    type="tel" 
                    name="no_hp"
                    maxlength="13"
                    placeholder="Contoh: 081234567890"
                    class="input input-bordered input-sm"
                    required
                />
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.no_hp ?? ''"></span>
            </div>

            <!-- Password -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">Password <span class="text-red-500">*</span></span>
                </label>
                <input 
                    type="password" 
                    name="password"
                    placeholder="Minimal 6 karakter"
                    class="input input-bordered input-sm"
                    required
                />
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.password ?? ''"></span>
            </div>

            <!-- Jabatan -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">Jabatan <span class="text-red-500">*</span></span>
                </label>
                <select 
                    name="jabatan"
                    class="select select-bordered select-sm"
                    required
                >
                    <option value="">-- Pilih Jabatan --</option>
                    <option value="Pengawas">Pengawas</option>
                    <option value="Panitia">Panitia</option>
                    <option value="Penguji">Penguji</option>
                </select>
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.jabatan ?? ''"></span>
            </div>
        </form>
    </x-slot>

    <x-slot name="footer">
        <button class="btn btn-ghost btn-sm" @click="closeAddModal()">Batal</button>
        <button class="btn btn-success btn-sm" @click="submitAddForm()" :disabled="isSubmitting">
            <span x-show="!isSubmitting">Simpan</span>
            <span x-show="isSubmitting" class="loading loading-spinner loading-sm"></span>
        </button>
    </x-slot>
</x-ui.modal>

{{-- Edit Panitia Modal --}}
<x-ui.modal id="editModal" size="md">
    <x-slot name="header">
        <h3 class="text-lg font-semibold text-slate-800">Edit Panitia</h3>
    </x-slot>

    <x-slot name="body">
        <form id="editForm" class="space-y-3">
            @csrf
            @method('PUT')
            
            <!-- ID Panitia (Read Only) -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">ID Panitia</span>
                </label>
                <input 
                    type="text" 
                    x-model="editData.id_panitia"
                    class="input input-bordered input-sm bg-base-200"
                    disabled
                />
                <label class="label">
                    <span class="label-text-alt text-base-content/50">ID tidak dapat diubah</span>
                </label>
            </div>

            <!-- Nama Lengkap -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">Nama Lengkap <span class="text-red-500">*</span></span>
                </label>
                <input 
                    type="text" 
                    name="nama_lengkap"
                    maxlength="30"
                    placeholder="Masukkan nama lengkap"
                    x-model="editData.nama_lengkap"
                    class="input input-bordered input-sm"
                    required
                />
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.nama_lengkap ?? ''"></span>
            </div>

            <!-- Username -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">Username <span class="text-red-500">*</span></span>
                </label>
                <input 
                    type="text" 
                    name="username"
                    maxlength="10"
                    placeholder="Masukkan username"
                    x-model="editData.username"
                    class="input input-bordered input-sm"
                    required
                />
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.username ?? ''"></span>
            </div>

            <!-- No HP -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">No. HP <span class="text-red-500">*</span></span>
                </label>
                <input 
                    type="tel" 
                    name="no_hp"
                    maxlength="13"
                    placeholder="Contoh: 081234567890"
                    x-model="editData.no_hp"
                    class="input input-bordered input-sm"
                    required
                />
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.no_hp ?? ''"></span>
            </div>

            <!-- Jabatan -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">Jabatan <span class="text-red-500">*</span></span>
                </label>
                <select 
                    name="jabatan"
                    x-model="editData.jabatan"
                    class="select select-bordered select-sm"
                    required
                >
                    <option value="">-- Pilih Jabatan --</option>
                    <option value="Pengawas">Pengawas</option>
                    <option value="Panitia">Panitia</option>
                    <option value="Penguji">Penguji</option>
                </select>
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.jabatan ?? ''"></span>
            </div>
        </form>
    </x-slot>

    <x-slot name="footer">
        <button class="btn btn-ghost btn-sm" @click="closeEditModal()">Batal</button>
        <button class="btn btn-success btn-sm" @click="submitEditForm()" :disabled="isSubmitting">
            <span x-show="!isSubmitting">Perbarui</span>
            <span x-show="isSubmitting" class="loading loading-spinner loading-sm"></span>
        </button>
    </x-slot>
</x-ui.modal>

{{-- Delete Confirmation Modal --}}
<x-ui.modal id="deleteModal" size="sm">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-slate-800">Konfirmasi Hapus</h3>
                <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan</p>
            </div>
        </div>
    </x-slot>

    <x-slot name="body">
        <p class="text-sm text-slate-600">
            Apakah Anda yakin ingin menghapus panitia <strong class="text-slate-800" id="deleteName"></strong>?
        </p>
    </x-slot>

    <x-slot name="footer">
        <button class="btn btn-ghost btn-sm" onclick="deleteModal.close()">Batal</button>
        <button class="btn btn-error btn-sm" onclick="confirmDelete()">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Hapus
        </button>
    </x-slot>
</x-ui.modal>

<script>
    let deleteId = null;

    function openDeleteModal(id, name) {
        deleteId = id;
        document.getElementById('deleteName').textContent = name;
        deleteModal.showModal();
    }

    function confirmDelete() {
        if (!deleteId) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/panitia/${deleteId}`;
        form.innerHTML = `
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    function panitiaManager() {
        return {
            searchTerm: '',
            sortColumn: null,
            sortDirection: 'asc',
            isSubmitting: false,
            rows: @json($rows),
            filteredRows: @json($rows),
            formErrors: {},
            editData: {
                id_panitia: '',
                nama_lengkap: '',
                username: '',
                no_hp: '',
                jabatan: ''
            },

            // Modal add
            openAddModal() {
                this.formErrors = {};
                document.getElementById('addForm').reset();
                addModal.showModal();
            },

            closeAddModal() {
                this.formErrors = {};
                document.getElementById('addForm').reset();
                addModal.close();
            },

            async submitAddForm() {
                this.isSubmitting = true;
                this.formErrors = {};
                
                const formData = new FormData(document.getElementById('addForm'));
                const data = Object.fromEntries(formData);

                try {
                    const response = await fetch('{{ route("panitia.store") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        this.formErrors = result.errors || {};
                        return;
                    }

                    // Reload page untuk update tabel
                    window.location.reload();
                } catch (error) {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menyimpan data');
                } finally {
                    this.isSubmitting = false;
                }
            },

            // Modal edit
            async openEditModal(id) {
                try {
                    const response = await fetch(`/panitia/${id}/edit`, {
                        headers: {
                            'Accept': 'application/json',
                        }
                    });

                    if (!response.ok) throw new Error('Failed to fetch');
                    
                    const data = await response.json();
                    this.editData = {
                        id_panitia: data.id_panitia,
                        nama_lengkap: data.nama_lengkap,
                        username: data.username,
                        no_hp: data.no_hp,
                        jabatan: data.jabatan
                    };
                    this.formErrors = {};
                    editModal.showModal();
                } catch (error) {
                    console.error('Error:', error);
                    alert('Gagal memuat data panitia');
                }
            },

            closeEditModal() {
                this.formErrors = {};
                this.editData = {
                    id_panitia: '',
                    nama_lengkap: '',
                    username: '',
                    no_hp: '',
                    jabatan: ''
                };
                editModal.close();
            },

            async submitEditForm() {
                this.isSubmitting = true;
                this.formErrors = {};

                const formData = new FormData(document.getElementById('editForm'));
                formData.set('nama_lengkap', this.editData.nama_lengkap);
                formData.set('username', this.editData.username);
                formData.set('no_hp', this.editData.no_hp);
                formData.set('jabatan', this.editData.jabatan);

                try {
                    const response = await fetch(`/panitia/${this.editData.id_panitia}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        this.formErrors = result.errors || {};
                        return;
                    }

                    // Reload page untuk update tabel
                    window.location.reload();
                } catch (error) {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memperbarui data');
                } finally {
                    this.isSubmitting = false;
                }
            },

            // Table functions
            filterAndSort() {
                const term = this.searchTerm.trim().toLowerCase();
                this.filteredRows = term
                    ? this.rows.filter(row => row.search.includes(term))
                    : [...this.rows];
                if (this.sortColumn) this.applySort();
            },

            toggleSort(column) {
                this.sortDirection = this.sortColumn === column
                    ? (this.sortDirection === 'asc' ? 'desc' : 'asc')
                    : 'asc';
                this.sortColumn = column;
                this.applySort();
            },

            applySort() {
                const dir = this.sortDirection === 'asc' ? 1 : -1;
                this.filteredRows.sort((a, b) => {
                    const aVal = a[this.sortColumn] ?? '';
                    const bVal = b[this.sortColumn] ?? '';
                    return typeof aVal === 'string'
                        ? aVal.localeCompare(bVal) * dir
                        : (aVal - bVal) * dir;
                });
            },

            init() {
                // Listen untuk edit modal dari tabel
                document.addEventListener('openEditModal', (e) => {
                    this.openEditModal(e.detail.id);
                });
            }
        };
    }

    function panitiaTable() {
        return {
            searchTerm: '',
            sortColumn: null,
            sortDirection: 'asc',
            rows: @json($rows),
            filteredRows: @json($rows),

            filterAndSort() {
                const term = this.searchTerm.trim().toLowerCase();

                this.filteredRows = term
                    ? this.rows.filter(row => row.search.includes(term))
                    : [...this.rows];

                if (this.sortColumn) this.applySort();
            },

            toggleSort(column) {
                this.sortDirection = this.sortColumn === column
                    ? (this.sortDirection === 'asc' ? 'desc' : 'asc')
                    : 'asc';
                this.sortColumn = column;
                this.applySort();
            },

            applySort() {
                const dir = this.sortDirection === 'asc' ? 1 : -1;
                this.filteredRows.sort((a, b) => {
                    const aVal = a[this.sortColumn] ?? '';
                    const bVal = b[this.sortColumn] ?? '';
                    return typeof aVal === 'string'
                        ? aVal.localeCompare(bVal) * dir
                        : (aVal - bVal) * dir;
                });
            },
        };
    }

</script>
</div>
@endsection
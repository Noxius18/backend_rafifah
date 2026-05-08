@extends('layouts.app')

@section('content')

@php
    $jenisKelaminOptions = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
    $statusOptions = [
        'Pendaftar Baru' => 'Pendaftar Baru',
        'Terverifikasi' => 'Terverifikasi',
        'Lulus' => 'Lulus',
        'Tidak Lulus' => 'Tidak Lulus',
    ];

    // ── HTML renderers untuk kolom tabel ───────────────────────────────────
    $columns = [
        ['label' => 'ID',           'field' => 'id_mahasantri', 'html' => 'id_html',       'class' => 'hidden md:table-cell'],
        ['label' => 'Nama',         'field' => 'nama_lengkap',  'html' => 'nama_html'],
        ['label' => 'NIK',          'field' => 'nik',                                       'class' => 'hidden lg:table-cell'],
        ['label' => 'NISN',         'field' => 'nisn',                                      'class' => 'hidden lg:table-cell'],
        ['label' => 'Jenis Kelamin','field' => 'jenis_kelamin', 'html' => 'jk_html',        'class' => 'hidden md:table-cell'],
        ['label' => 'Tempat Lahir', 'field' => 'tempat_lahir',                              'class' => 'hidden xl:table-cell'],
        ['label' => 'Tgl Lahir',    'field' => 'tanggal_lahir', 'html' => 'tgl_lahir_html', 'class' => 'hidden xl:table-cell'],
        ['label' => 'Status',       'field' => 'status',        'html' => 'status_html',    'class' => 'hidden sm:table-cell'],
        ['label' => 'Tgl Daftar',   'field' => 'tanggal_daftar','html' => 'tgl_html',       'class' => 'hidden sm:table-cell'],
        ['label' => 'Aksi',         'field' => 'id_mahasantri', 'html' => 'aksi_html',      'class' => 'text-right'],
    ];

    $rows = $mahasantris->map(fn($m) => [
        // Plain — untuk sort
        'id_mahasantri'  => $m->id_mahasantri,
        'nama_lengkap'   => $m->nama_lengkap,
        'nik'            => $m->nik ?? '-',
        'nisn'           => $m->nisn ?? '-',
        'jenis_kelamin'  => $m->jenis_kelamin ?? '-',
        'tempat_lahir'   => $m->tempat_lahir ?? '-',
        'tanggal_lahir'  => $m->tanggal_lahir ? (is_string($m->tanggal_lahir) ? $m->tanggal_lahir : $m->tanggal_lahir->format('d/m/Y')) : '-',
        'status'         => $m->status,
        'tanggal_daftar' => $m->tanggal_daftar ? (is_string($m->tanggal_daftar) ? $m->tanggal_daftar : $m->tanggal_daftar->format('d/m/Y')) : '-',

        // HTML — untuk render
        'id_html'   => "<code class='rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500'>{$m->id_mahasantri}</code>",
        'nama_html' => "<div class='flex items-center gap-2.5'>
                            <div class='flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-700'>" . strtoupper(substr($m->nama_lengkap, 0, 1)) . "</div>
                            <span class='font-medium text-slate-700'>" . e($m->nama_lengkap) . "</span>
                        </div>",
        'jk_html'   => match($m->jenis_kelamin) {
            'L' => "<span class='rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-200'>Laki-laki</span>",
            'P' => "<span class='rounded-md bg-pink-50 px-2 py-0.5 text-xs font-medium text-pink-700 ring-1 ring-pink-200'>Perempuan</span>",
            default => "<span class='text-slate-400'>-</span>",
        },
        'tgl_lahir_html' => $m->tanggal_lahir
            ? "<span class='text-xs text-slate-500'>" . (is_string($m->tanggal_lahir) ? $m->tanggal_lahir : $m->tanggal_lahir->format('d/m/Y')) . "</span>"
            : "<span class='text-slate-400'>-</span>",
        'status_html' => match($m->status) {
            'Pendaftar Baru' => "<span class='rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200'>Pendaftar Baru</span>",
            'Terverifikasi'  => "<span class='rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-sky-200'>Terverifikasi</span>",
            'Lulus'          => "<span class='rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200'>Lulus</span>",
            'Tidak Lulus'    => "<span class='rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200'>Tidak Lulus</span>",
            default          => "<span class='text-slate-400'>" . e($m->status) . "</span>",
        },
        'tgl_html'  => "<span class='text-xs text-slate-500'>" . ($m->tanggal_daftar ? (is_string($m->tanggal_daftar) ? $m->tanggal_daftar : $m->tanggal_daftar->format('d/m/Y')) : '-') . "</span>",
        'aksi_html' => "<div class='flex items-center justify-end gap-1'>
                            <button type='button'
                                onclick=\"openDetailModal('{$m->id_mahasantri}')\"
                                class='rounded-md px-2.5 py-1 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700'>Detail</button>
                            <span class='text-slate-200'>|</span>
                            <button type='button'
                                onclick=\"openEditModal({
                                    id:           '{$m->id_mahasantri}',
                                    nama:         '" . e($m->nama_lengkap) . "',
                                    nik:          '" . e($m->nik ?? '') . "',
                                    nisn:         '" . e($m->nisn ?? '') . "',
                                    jk:           '" . e($m->jenis_kelamin ?? '') . "',
                                    tempat_lahir: '" . e($m->tempat_lahir ?? '') . "',
                                    tgl_lahir:    '" . ($m->tanggal_lahir ? (is_string($m->tanggal_lahir) ? $m->tanggal_lahir : $m->tanggal_lahir->format('Y-m-d')) : '') . "',
                                    status:       '" . e($m->status) . "'
                                })\"
                                class='rounded-md px-2.5 py-1 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700'>Edit</button>
                            <span class='text-slate-200'>|</span>
                            <button type='button'
                                onclick=\"openConfirmModal('/mahasantri/{$m->id_mahasantri}', '" . e($m->nama_lengkap) . "')\"
                                class='rounded-md px-2.5 py-1 text-xs font-medium text-slate-500 transition hover:bg-rose-50 hover:text-rose-600'>Hapus</button>
                        </div>",

        // Search index
        'search' => strtolower("{$m->id_mahasantri} {$m->nama_lengkap} {$m->nik} {$m->nisn} {$m->tempat_lahir} {$m->status}"),
    ])->toArray();
@endphp

<div x-data="{
    toast: { show: false, message: '', type: 'success', timer: null },
    showToast(message, type = 'success') {
        if (this.toast.timer) clearTimeout(this.toast.timer);
        Object.assign(this.toast, { message, type, show: true });
        this.toast.timer = setTimeout(() => this.toast.show = false, 4000);
    },
    detailData: null,
    loadingDetail: false,
    async openDetail(id) {
        this.loadingDetail = true;
        this.detailData = null;
        try {
            const res = await fetch(`/mahasantri/${id}`);
            this.detailData = await res.json();
        } catch(e) {
            this.showToast('Gagal memuat data detail', 'error');
        } finally {
            this.loadingDetail = false;
        }
    }
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
                        <h1 class="text-xl font-semibold text-slate-800">Kelola Data Mahasantri</h1>
                        <p class="text-sm text-slate-400">Kelola data pendaftaran mahasantri.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="importModal.showModal()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 active:scale-95">
                        <x-heroicon-s-arrow-up-tray class="h-4 w-4" />
                        Import CSV
                    </button>
                    <button type="button" onclick="addModal.showModal()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95">
                        <x-heroicon-s-user-plus class="h-4 w-4" />
                        Tambah
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <x-ui.data-table
                    :rows="$rows"
                    :columns="$columns"
                    :total="$mahasantris->count()"
                    empty-message="Belum ada data mahasantri"
                    empty-sub="Mulai dengan menambahkan mahasantri pertama atau import CSV."
                    add-label="Tambah Mahasantri"
                />
            </div>

        </section>
    </x-ui.sidebar>

    {{-- ── Modal: Tambah ──────────────────────────────────────────────── --}}
    <x-ui.modal-form id="addModal" title="Tambah Mahasantri Baru">
        <x-slot name="body">
            <form id="addModal-form" action="{{ route('mahasantri.store') }}" method="POST" class="space-y-3">
                @csrf
                <x-ui.form-input name="nama_lengkap" label="Nama Lengkap" placeholder="Masukkan nama lengkap" maxlength="35" required />
                <x-ui.form-input name="nik"          label="NIK"           placeholder="16 digit NIK"        maxlength="16" />
                <x-ui.form-input name="nisn"         label="NISN"          placeholder="10 digit NISN"       maxlength="10" />
                <x-ui.form-select name="jenis_kelamin" label="Jenis Kelamin" :options="$jenisKelaminOptions" placeholder="Pilih jenis kelamin" />
                <x-ui.form-input name="tempat_lahir" label="Tempat Lahir"  placeholder="Masukkan tempat lahir" maxlength="50" />
                <x-ui.form-input name="tanggal_lahir" label="Tanggal Lahir" type="date" />
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="addModal.close()">Batal</button>
            <button type="submit" form="addModal-form" class="btn btn-success btn-sm">Simpan</button>
        </x-slot>
    </x-ui.modal-form>

    {{-- ── Modal: Edit ────────────────────────────────────────────────── --}}
    <x-ui.modal-form id="editModal" title="Edit Mahasantri">
        <x-slot name="body">
            <form id="editModal-form" action="" method="POST" class="space-y-3">
                @csrf
                @method('PUT')

                {{-- ID readonly --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold text-sm">ID Mahasantri</span>
                    </label>
                    <input type="text" id="editModal-id-display" class="input input-bordered input-sm bg-base-200" disabled />
                    <label class="label">
                        <span class="label-text-alt text-base-content/50">ID tidak dapat diubah</span>
                    </label>
                </div>

                <x-ui.form-input name="nama_lengkap" label="Nama Lengkap" placeholder="Masukkan nama lengkap" maxlength="35" required />
                <x-ui.form-input name="nik"          label="NIK"           placeholder="16 digit NIK"        maxlength="16" />
                <x-ui.form-input name="nisn"         label="NISN"          placeholder="10 digit NISN"       maxlength="10" />
                <x-ui.form-select name="jenis_kelamin" label="Jenis Kelamin" :options="$jenisKelaminOptions" placeholder="Pilih jenis kelamin" />
                <x-ui.form-input name="tempat_lahir" label="Tempat Lahir"  placeholder="Masukkan tempat lahir" maxlength="50" />
                <x-ui.form-input name="tanggal_lahir" label="Tanggal Lahir" type="date" />
                <x-ui.form-select name="status" label="Status" :options="$statusOptions" />
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="editModal.close()">Batal</button>
            <button type="submit" form="editModal-form" class="btn btn-success btn-sm">Perbarui</button>
        </x-slot>
    </x-ui.modal-form>

    {{-- ── Modal: Detail ──────────────────────────────────────────────── --}}
    <x-ui.modal-form id="detailModal" title="Detail Mahasantri">
        <x-slot name="body">
            <div x-show="loadingDetail" class="flex items-center justify-center py-8">
                <span class="loading loading-spinner loading-md text-indigo-600"></span>
                <span class="ml-2 text-sm text-slate-500">Memuat data...</span>
            </div>

            <template x-if="detailData && !loadingDetail">
                <div class="space-y-4">
                    {{-- Data Pribadi --}}
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-slate-700">Data Pribadi</h3>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <dl class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <dt class="text-slate-400">ID</dt>
                                    <dd class="font-medium text-slate-700" x-text="detailData.id_mahasantri"></dd>
                                </div>
                                <div>
                                    <dt class="text-slate-400">Nama Lengkap</dt>
                                    <dd class="font-medium text-slate-700" x-text="detailData.nama_lengkap"></dd>
                                </div>
                                <div>
                                    <dt class="text-slate-400">NIK</dt>
                                    <dd class="font-medium text-slate-700" x-text="detailData.nik || '-'"></dd>
                                </div>
                                <div>
                                    <dt class="text-slate-400">NISN</dt>
                                    <dd class="font-medium text-slate-700" x-text="detailData.nisn || '-'"></dd>
                                </div>
                                <div>
                                    <dt class="text-slate-400">Jenis Kelamin</dt>
                                    <dd class="font-medium text-slate-700" x-text="detailData.jenis_kelamin === 'L' ? 'Laki-laki' : detailData.jenis_kelamin === 'P' ? 'Perempuan' : '-'"></dd>
                                </div>
                                <div>
                                    <dt class="text-slate-400">Tempat, Tgl Lahir</dt>
                                    <dd class="font-medium text-slate-700" x-text="(detailData.tempat_lahir || '-') + ', ' + (detailData.tanggal_lahir || '-')"></dd>
                                </div>
                                <div>
                                    <dt class="text-slate-400">Status</dt>
                                    <dd class="font-medium text-slate-700" x-text="detailData.status"></dd>
                                </div>
                                <div>
                                    <dt class="text-slate-400">Tanggal Daftar</dt>
                                    <dd class="font-medium text-slate-700" x-text="detailData.tanggal_daftar || '-'"></dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- Data Orangtua --}}
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-slate-700">Data Orangtua / Wali</h3>
                        <template x-if="detailData.orangtuas && detailData.orangtuas.length > 0">
                            <div class="space-y-2">
                                <template x-for="ort in detailData.orangtuas" :key="ort.id_orangtua">
                                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                                        <div class="mb-1">
                                            <span class="rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200" x-text="ort.tipe_hubungan"></span>
                                        </div>
                                        <dl class="grid grid-cols-3 gap-2 text-sm">
                                            <div>
                                                <dt class="text-slate-400">Nama</dt>
                                                <dd class="font-medium text-slate-700" x-text="ort.nama_lengkap"></dd>
                                            </div>
                                            <div>
                                                <dt class="text-slate-400">Pekerjaan</dt>
                                                <dd class="font-medium text-slate-700" x-text="ort.pekerjaan || '-'"></dd>
                                            </div>
                                            <div>
                                                <dt class="text-slate-400">No. WA</dt>
                                                <dd class="font-medium text-slate-700" x-text="ort.no_wa || '-'"></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!detailData.orangtuas || detailData.orangtuas.length === 0">
                            <p class="text-sm text-slate-400 italic">Belum ada data orangtua.</p>
                        </template>
                    </div>

                    {{-- Data Dokumen --}}
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-slate-700">Dokumen</h3>
                        <template x-if="detailData.berkas && detailData.berkas.length > 0">
                            <div class="space-y-2">
                                <template x-for="doc in detailData.berkas" :key="doc.id_berkas">
                                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200" x-text="doc.tipe_dokumen"></span>
                                                <span x-show="doc.is_valid" class="rounded-md bg-emerald-50 px-1.5 py-0.5 text-xs font-medium text-emerald-700">Terverifikasi</span>
                                                <span x-show="!doc.is_valid" class="rounded-md bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-700">Belum Verifikasi</span>
                                            </div>
                                            <a :href="doc.url" target="_blank" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-indigo-600 transition hover:bg-indigo-50">
                                                <x-heroicon-s-eye class="h-3.5 w-3.5" />
                                                Lihat
                                            </a>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!detailData.berkas || detailData.berkas.length === 0">
                            <p class="text-sm text-slate-400 italic">Belum ada dokumen.</p>
                        </template>
                    </div>
                </div>
            </template>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="detailModal.close()">Tutup</button>
        </x-slot>
    </x-ui.modal-form>

    {{-- ── Modal: Import CSV ──────────────────────────────────────────── --}}
    <x-ui.modal-form id="importModal" title="Import Data Mahasantri dari CSV">
        <x-slot name="body">
            <div class="space-y-4">
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    <p class="font-medium">Petunjuk:</p>
                    <ul class="mt-1 list-inside list-disc space-y-0.5 text-amber-700">
                        <li>File harus berformat <strong>.csv</strong></li>
                        <li>Header file harus sesuai dengan template yang disediakan</li>
                        <li>Data akan diimpor ke 3 tabel: Mahasantri, Orangtua, dan Dokumen</li>
                        <li>Maksimal ukuran file 10MB</li>
                    </ul>
                </div>

                <form id="importModal-form" action="{{ route('mahasantri.import') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold text-sm">Pilih File CSV</span>
                        </label>
                        <input type="file" name="file" accept=".csv,.txt" class="file-input file-input-bordered file-input-sm w-full" required />
                        <label class="label">
                            <span class="label-text-alt text-base-content/50">Format: .csv (comma separated values)</span>
                        </label>
                    </div>
                </form>

                {{-- Template Preview --}}
                <details class="rounded-lg border border-slate-200">
                    <summary class="cursor-pointer px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Lihat template CSV</summary>
                    <div class="border-t border-slate-200 p-3">
                        <p class="mb-2 text-xs text-slate-500">Header CSV yang didukung:</p>
                        <code class="block whitespace-pre-wrap rounded bg-slate-50 p-2 text-[10px] leading-relaxed text-slate-600">
Cap waktu,Nama Lengkap,Nama Ayah Kandung,Pekerjaan Ayah,No HP/Whatsap Ayah Yang Aktif,Nama Ibu Kandung,Pekerjaan Ibu,No HP/Whatsap Ibu Yang Aktif,Nama Wali (jika peserta di tanggung oleh selain orang tua kandung),Pekerjaan Wali,Nomer HP Wali,Scan KTP asli,Scan Kartu Keluarga asli,Scan Ijazah terakhir,Surat izin Orang tua
                        </code>
                    </div>
                </details>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="importModal.close()">Batal</button>
            <button type="submit" form="importModal-form" class="btn btn-success btn-sm">Import</button>
        </x-slot>
    </x-ui.modal-form>

    {{-- ── Modal: Hapus ───────────────────────────────────────────────── --}}
    <x-ui.modal-confirm
        id="deleteModal"
        title="Konfirmasi Hapus"
        body-text="Apakah Anda yakin ingin menghapus data mahasantri"
        confirm-label="Hapus"
    />

</div>

{{-- JS minimal — hanya mengisi field form edit & detail fetch --}}
<script>
    function openEditModal({ id, nama, nik, nisn, jk, tempat_lahir, tgl_lahir, status }) {
        document.getElementById('editModal-form').action = `/mahasantri/${id}`;

        document.getElementById('editModal-id-display').value = id;
        document.querySelector('#editModal-form [name="nama_lengkap"]').value = nama;
        document.querySelector('#editModal-form [name="nik"]').value = nik;
        document.querySelector('#editModal-form [name="nisn"]').value = nisn;
        document.querySelector('#editModal-form [name="jenis_kelamin"]').value = jk;
        document.querySelector('#editModal-form [name="tempat_lahir"]').value = tempat_lahir;
        document.querySelector('#editModal-form [name="tanggal_lahir"]').value = tgl_lahir;
        document.querySelector('#editModal-form [name="status"]').value = status;

        editModal.showModal();
    }

    function openDetailModal(id) {
        const alpine = document.querySelector('[x-data]').__x;
        alpine.$data.openDetail(id);
        detailModal.showModal();
    }
</script>

@endsection
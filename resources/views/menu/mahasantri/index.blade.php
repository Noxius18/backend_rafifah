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

    $tahun = date('y');
    $gelombangList = [
        ['value' => '1', 'label' => "Gelombang 1"],
        ['value' => '2', 'label' => "Gelombang 2"],
    ];

    $columns = [
        ['label' => 'Kode Pendaftar',           'field' => 'id_mahasantri', 'html' => 'id_html'],
        ['label' => 'Nama',         'field' => 'nama_lengkap',  'html' => 'nama_html'],
        ['label' => 'Status',       'field' => 'status',        'html' => 'status_html'],
        ['label' => 'Aksi',         'field' => 'id_mahasantri', 'html' => 'aksi_html', 'class' => 'text-right'],
    ];

    $rows = $mahasantris->map(function($m) {
        
        // PERBAIKAN LOGIKA: Cukup baca dari status mahasantri-nya saja.
        // Tombol Cetak PDF HANYA menyala jika statusnya final (Lulus / Tidak Lulus)
        $isFinal = in_array($m->status, ['Lulus', 'Tidak Lulus']);
        
        $printBtnHtml = $isFinal
            ? '<a href="' . route('mahasantri.cetak-pdf', $m->id_mahasantri) . '" target="_blank" class="p-2 rounded-md text-black hover:text-amber-600 hover:bg-amber-50 transition" title="Cetak Surat Kelulusan">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
               </a>'
            : '<button type="button" disabled class="p-2 rounded-md text-slate-300 cursor-not-allowed" title="Menunggu Keputusan Kelulusan / Belum Tes">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
               </button>';

        return [
            'id_mahasantri'  => $m->id_mahasantri,
            'nama_lengkap'   => $m->nama_lengkap,
            'status'         => $m->status,
            'search'         => strtolower("{$m->id_mahasantri} {$m->nama_lengkap} {$m->nik} {$m->nisn} {$m->tempat_lahir} {$m->status}"),

            'id_html'   => "<code class='rounded bg-black/[0.05] px-1.5 py-0.5 text-xs text-black'>{$m->id_mahasantri}</code>",
            'nama_html' => "<span class='font-medium text-black'>" . e($m->nama_lengkap) . "</span>",
            'status_html' => match($m->status) {
                'Pendaftar Baru' => "<span class='rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200'>Pendaftar Baru</span>",
                'Terverifikasi'  => "<span class='rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-sky-200'>Terverifikasi</span>",
                'Lulus'          => "<span class='rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200'>Lulus</span>",
                'Tidak Lulus'    => "<span class='rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200'>Tidak Lulus</span>",
                default          => "<span class='text-black'>" . e($m->status) . "</span>",
            },
            
            'aksi_html' => '<div class="flex items-center justify-end gap-0.5">
                                ' . $printBtnHtml . '
                                <a href="' . route('mahasantri.show', $m->id_mahasantri) . '" class="p-2 rounded-md text-black hover:text-indigo-600 hover:bg-indigo-50 transition" title="Detail">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </a>
                                ' . (auth()->user()->jabatan === 'Panitia' ? '
                                <div x-data="{ open: false, top: 0, left: 0 }" @click.outside="open = false" class="relative">
                                    <button type="button" @click="const rect = $el.getBoundingClientRect(); top = rect.bottom + window.scrollY; left = rect.right - 192; open = !open" class="p-2 rounded-md text-black hover:bg-black/[0.05] transition" title="Lainnya">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
                                    </button>
                                    <template x-teleport="body">
                                        <ul x-show="open" :style="`position: absolute; top: ${top}px; left: ${left}px; z-index: 9999;`" class="menu w-48 bg-white border border-black/10 shadow-xl rounded-box p-1">
                                            ' . ($m->status === 'Pendaftar Baru' ? '
                                            <li><a href="#" onclick="bukaModalVerif(\'' . $m->id_mahasantri . '\', \'' . e($m->nama_lengkap) . '\'); open=false" class="text-black hover:text-amber-600 hover:bg-amber-50 group"><svg class="h-4 w-4 text-black group-hover:text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Verifikasi</a></li>
                                            ' : '') . '
                                            <li><a href="#" onclick="editMahasantri(\'' . $m->id_mahasantri . '\', \'' . e($m->nama_lengkap) . '\', \'' . e($m->nik ?? '') . '\', \'' . e($m->nisn ?? '') . '\', \'' . e($m->jenis_kelamin ?? '') . '\', \'' . e($m->tempat_lahir ?? '') . '\', \'' . ($m->tanggal_lahir ? (is_string($m->tanggal_lahir) ? $m->tanggal_lahir : $m->tanggal_lahir->format('Y-m-d')) : '') . '\', \'' . e($m->status) . '\'); open=false" class="text-black hover:text-emerald-600 hover:bg-emerald-50 group"><svg class="h-4 w-4 text-black group-hover:text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg> Edit</a></li>
                                            <li><a href="#" onclick="openConfirmModal(\'/mahasantri/' . $m->id_mahasantri . '\', \'' . e($m->nama_lengkap) . '\'); open=false" class="text-black hover:text-rose-600 hover:bg-rose-50 group"><svg class="h-4 w-4 text-black group-hover:text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg> Hapus</a></li>
                                        </ul>
                                    </template>
                                </div>
                                ' : '') . '
                            </div>',
        ];
    })->toArray();
@endphp

<div x-data="{
    toast: { show: false, message: '', type: 'success', timer: null },
    showToast(message, type = 'success') {
        if (this.toast.timer) clearTimeout(this.toast.timer);
        Object.assign(this.toast, { message, type, show: true });
        this.toast.timer = setTimeout(() => this.toast.show = false, 4000);
    },
}" x-init="@if(session('success')) showToast('{{ session('success') }}') @endif @if(session('error')) showToast('{{ session('error') }}', 'error') @endif">

    <x-ui.toast />
    <x-ui.sidebar>
        <section class="space-y-6 px-1 py-2">
            <div class="flex items-center justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-1 h-7 w-1 rounded-full bg-emerald-500"></div>
                    <div>
                        <h1 class="text-xl font-semibold text-black">Kelola Calon Mahasantri</h1>
                        <p class="text-sm text-black">Kelola data pendaftaran calon mahasantri.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if(auth()->user()->jabatan === 'Panitia')
                    <button type="button" onclick="document.getElementById('importModal').showModal()" class="inline-flex items-center gap-1.5 rounded-lg border border-black/20 bg-white px-3.5 py-2 text-sm font-medium text-black transition hover:bg-black/[0.03] active:scale-95"><x-heroicon-s-arrow-up-tray class="h-4 w-4" /> Upload Excel</button>
                    @endif

                    @if(auth()->user()->jabatan === 'Pengawas')
                    <button type="button" onclick="document.getElementById('hapusSemuaModal').showModal()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3.5 py-2 text-sm font-medium text-white shadow-md transition hover:bg-red-700 active:bg-red-800">
                        <x-heroicon-s-trash class="h-4 w-4" /> Hapus Semua Data
                    </button>
                    @endif
                </div>
            </div>

            {{-- Filter Chips Gelombang --}}
            <div class="flex items-center gap-2">
                <a href="{{ route('mahasantri.index') }}" class="rounded-lg px-3 py-1.5 text-xs font-medium transition {{ !$filterGelombang ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Semua</a>
                @foreach($gelombangList as $g)
                    <a href="{{ route('mahasantri.index', array_filter(['gelombang' => $g['value'], 'page' => null])) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition {{ $filterGelombang === $g['value'] ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/></svg>
                        {{ $g['label'] }}
                    </a>
                @endforeach
            </div>

            {{-- Import Errors Detail (dismissible, auto-hide 15s) --}}
            @php $importErrors = session('import_errors'); @endphp
            @if(is_array($importErrors) && count($importErrors) > 0)
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 15000)"
                     class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                    <div class="flex items-start gap-3">
                        <x-heroicon-s-exclamation-circle class="mt-0.5 h-5 w-5 shrink-0 text-rose-500" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-rose-800">
                                {{ count($importErrors) }} baris gagal diimpor. Perbaiki data Excel lalu upload ulang:
                            </p>
                            <ul class="mt-2 max-h-64 space-y-1 overflow-y-auto pr-2">
                                @foreach($importErrors as $error)
                                    <li class="flex items-start gap-2 text-sm text-rose-700">
                                        <span class="mt-1 inline-block h-1 w-1 shrink-0 rounded-full bg-rose-400"></span>
                                        <span>{{ $error }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" @click="show = false"
                                class="shrink-0 rounded-lg p-1 text-rose-400 transition hover:bg-rose-100 hover:text-rose-600"
                                aria-label="Tutup">
                            <x-heroicon-s-x-mark class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            @endif

            <div class="overflow-hidden rounded-xl border border-black/20 bg-white">
                <x-ui.data-table :rows="$rows" :columns="$columns" :total="$mahasantris->total()" empty-message="Belum ada data" />
                <x-ui.pagination :paginator="$mahasantris" alwaysShow="true" />
            </div>
        </section>
    </x-ui.sidebar>

    <x-ui.modal-form id="editModal" title="Edit Mahasantri">
        <x-slot name="body">
            <div class="max-h-[65vh] overflow-y-auto -mr-2 pr-2">
            <form id="editModal-form" action="" method="POST" class="space-y-3">
                @csrf @method('PUT')
                <div class="form-control"><label class="label"><span class="label-text font-semibold text-sm">ID Mahasantri</span></label><input type="text" id="editModal-id-display" class="input input-bordered input-sm bg-base-200" disabled /></div>
                <x-ui.form-input name="nama_lengkap" label="Nama Lengkap" placeholder="Masukkan nama lengkap" maxlength="35" required />
                <x-ui.form-input name="nik" label="NIK" placeholder="16 digit NIK" maxlength="16" />
                <x-ui.form-input name="nisn" label="NISN" placeholder="10 digit NISN" maxlength="10" />
                <x-ui.form-select name="jenis_kelamin" label="Jenis Kelamin" :options="$jenisKelaminOptions" placeholder="Pilih jenis kelamin" />
                <x-ui.form-input name="tempat_lahir" label="Tempat Lahir" placeholder="Masukkan tempat lahir" maxlength="50" />
                <x-ui.form-input name="tanggal_lahir" label="Tanggal Lahir" type="date" />
                <x-ui.form-select name="status" label="Status" :options="$statusOptions" />
            </form>
            </div>
        </x-slot>
        <x-slot name="footer"><button type="button" class="btn btn-ghost btn-sm text-black hover:bg-black/[0.05]" onclick="document.getElementById('editModal').close()">Batal</button><button type="submit" form="editModal-form" class="btn btn-success btn-sm">Perbarui</button></x-slot>
    </x-ui.modal-form>

    <x-ui.modal-form id="importModal" title="Import Data Mahasantri dari Excel">
        <x-slot name="body">
            <div class="space-y-4">
                <div class="rounded-lg bg-sky-50 border border-sky-200 p-3 text-sm text-sky-700">
                    <div class="flex items-start gap-2">
                        <x-heroicon-s-information-circle class="h-5 w-5 mt-0.5 shrink-0" />
                        <div>
                            <strong class="font-semibold">Gelombang otomatis</strong>
                            <p class="mt-0.5 text-sky-600">Gelombang akan dideteksi otomatis berdasarkan tanggal daftar di file Excel. Pastikan tanggal daftar berada dalam rentang yang sudah dikonfigurasi.</p>
                        </div>
                    </div>
                </div>
                <form id="importModal-form" action="{{ route('mahasantri.import') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div class="form-control"><label class="label"><span class="label-text font-semibold text-sm">Pilih File Excel</span></label><input type="file" name="file" accept=".xlsx,.xls,.csv" class="file-input file-input-bordered file-input-sm w-full" required /></div>
                </form>
            </div>
        </x-slot>
        <x-slot name="footer"><button type="button" class="btn btn-ghost btn-sm text-black hover:bg-black/[0.05]" onclick="document.getElementById('importModal').close()">Batal</button><button type="submit" form="importModal-form" class="btn btn-success btn-sm">Upload</button></x-slot>
    </x-ui.modal-form>

    <x-ui.modal-confirm id="deleteModal" title="Konfirmasi Hapus" body-text="Apakah Anda yakin ingin menghapus data mahasantri" confirm-label="Hapus" />

    {{-- HAPUS MASSAL: Modal Semua Tahun Ini --}}
    <dialog id="hapusSemuaModal" class="modal">
        <div class="modal-box max-w-md p-6 rounded-2xl shadow-xl border border-black/10">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100">
                    <x-heroicon-s-fire class="h-6 w-6 text-red-500" />
                </div>
                <div class="min-w-0">
                    <h3 class="text-lg font-bold text-slate-800">Hapus Semua Data</h3>
                    <p class="mt-1 text-sm text-red-600 font-medium">TIDAK DAPAT DIBATALKAN</p>
                </div>
            </div>
            <div class="mt-4 space-y-3 rounded-lg bg-red-50 p-4 text-sm text-red-800">
                <p class="font-semibold">Tindakan ini akan menghapus SELURUH data 2 gelombang terakhir:</p>
                <ul class="ml-4 list-disc space-y-1 text-red-700">
                    <li>Semua mahasantri (Gelombang 1 &amp; 2)</li>
                    <li>Semua data orang tua/wali</li>
                    <li>Semua dokumen &amp; file berkas</li>
                    <li>Semua jadwal tes &amp; penguji</li>
                    <li><strong>Semua nilai ujian &amp; hasil seleksi — HILANG PERMANEN</strong></li>
                </ul>
            </div>
            <form id="hapusSemuaForm" action="{{ route('mahasantri.destroy-by-tahun-ajaran') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-black/10 bg-white p-3 transition hover:bg-slate-50">
                    <input type="checkbox" name="hapus_file_fisik" value="1" class="checkbox checkbox-sm checkbox-error mt-0.5" />
                    <div class="text-sm text-slate-600">
                        <span class="font-medium text-slate-800">Hapus file dokumen fisik</span>
                        <br /><span class="text-xs text-slate-400">File di storage akan ikut terhapus. Centang jika ingin membersihkan disk.</span>
                    </div>
                </label>
                <div class="modal-action mt-2 gap-2">
                    <button type="button" class="btn btn-ghost btn-sm text-slate-500 hover:bg-slate-100" onclick="document.getElementById('hapusSemuaModal').close()">Batal</button>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-md transition hover:bg-red-700 active:bg-red-800 border-none">
                        <x-heroicon-s-trash class="h-4 w-4" /> Hapus Semua
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    {{-- MODAL VERIFIKASI KHUSUS (KUNING & EXCLAMATION) --}}
    <dialog id="verifModal" class="modal">
        <div class="modal-box max-w-sm p-6 text-center rounded-2xl shadow-xl border border-black/10">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 mb-4">
                <svg class="h-8 w-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800">Konfirmasi Verifikasi</h3>
            <p class="py-2 text-sm text-slate-500">Apakah Anda yakin ingin memverifikasi data mahasantri <strong id="verifModal-name" class="text-slate-700"></strong>?</p>
            <div class="modal-action justify-center gap-2 mt-4">
                <form method="dialog"><button class="btn btn-ghost btn-sm text-slate-500 hover:bg-slate-100">Batal</button></form>
                <form id="verifModal-form" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm bg-amber-500 hover:bg-amber-600 text-white border-none shadow">Verifikasi</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
</div>

<script>
    function bukaModalVerif(id, nama) {
        document.getElementById('verifModal-name').textContent = nama;
        document.getElementById('verifModal-form').action = `/mahasantri/${id}/verifikasi`;
        document.getElementById('verifModal').showModal();
    }
    function editMahasantri(id, nama, nik, nisn, jk, tempat_lahir, tgl_lahir, status) {
        document.getElementById('editModal-form').action = `/mahasantri/${id}`;
        document.getElementById('editModal-id-display').value = id;
        document.querySelector('#editModal-form [name="nama_lengkap"]').value = nama;
        document.querySelector('#editModal-form [name="nik"]').value = nik;
        document.querySelector('#editModal-form [name="nisn"]').value = nisn;
        document.querySelector('#editModal-form [name="jenis_kelamin"]').value = jk;
        document.querySelector('#editModal-form [name="tempat_lahir"]').value = tempat_lahir;
        document.querySelector('#editModal-form [name="tanggal_lahir"]').value = tgl_lahir;
        document.querySelector('#editModal-form [name="status"]').value = status;
        document.getElementById('editModal').showModal();
    }
    function openConfirmModal(url, nama) {
        document.getElementById('deleteModal-name').textContent = nama;
        let form = document.getElementById('deleteModal-form');
        form.action = url;
        document.getElementById('deleteModal').showModal();
    }
</script>
@endsection
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
    $m = $mahasantri;
@endphp

<div x-data="{
    toast: { show: false, message: '', type: 'success', timer: null },
    showToast(message, type = 'success') {
        if (this.toast.timer) clearTimeout(this.toast.timer);
        Object.assign(this.toast, { message, type, show: true });
        this.toast.timer = setTimeout(() => this.toast.show = false, 4000);
    },

    // ── Preview Dokumen (Iframe Modal) ─────────────────────────────────
    previewUrl: '',
    previewTitle: '',
    previewBerkasId: null,
    previewIsValid: false,
    previewTempIsValid: false,
    saving: false,
    // Field data mahasantri untuk diisi di modal preview
    previewNik: '',
    previewNisn: '',
    previewTempatLahir: '',
    previewTanggalLahir: '',
    openPreview(url, title, berkasId, isValid, nik, nisn, tempatLahir, tanggalLahir) {
        this.previewUrl = url;
        this.previewTitle = title;
        this.previewBerkasId = berkasId;
        this.previewIsValid = isValid;
        this.previewTempIsValid = isValid;
        this.previewNik = nik || '';
        this.previewNisn = nisn || '';
        this.previewTempatLahir = tempatLahir || '';
        this.previewTanggalLahir = tanggalLahir || '';
        document.getElementById('previewModal').showModal();
    },

    // ── Retry Download ─────────────────────────────────────────────────
    async retryDownload(berkasId) {
        const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
        try {
            const res = await fetch(`/berkas/${berkasId}/retry-download`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();
            if (res.ok) {
                this.showToast(data.message || 'Proses unduh ulang dimulai');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                this.showToast(data.message || 'Gagal memulai unduh ulang', 'error');
            }
        } catch (e) {
            this.showToast('Gagal menghubungi server', 'error');
        }
    },

    // ── Toggle di dalam modal (hanya ubah state lokal) ─────────────────
    togglePreviewStatus() {
        this.previewTempIsValid = !this.previewTempIsValid;
    },

    // ── Simpan perubahan status via AJAX lalu reload ───────────────────
    async saveBerkasStatus() {
        this.saving = true;
        const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');

        try {
            const res = await fetch(`/berkas/${this.previewBerkasId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    is_valid: this.previewTempIsValid,
                    nik: this.previewNik || null,
                    nisn: this.previewNisn || null,
                    tempat_lahir: this.previewTempatLahir || null,
                    tanggal_lahir: this.previewTanggalLahir || null,
                }),
            });

            const data = await res.json();

            if (res.ok) {
                this.showToast(data.message || 'Status berhasil diperbarui');
                // Reload halaman untuk refresh data
                setTimeout(() => window.location.reload(), 500);
            } else {
                this.showToast(data.message || 'Gagal memperbarui status', 'error');
                this.saving = false;
            }
        } catch (e) {
            this.showToast('Gagal menghubungi server', 'error');
            this.saving = false;
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
                        <h1 class="text-xl font-semibold text-slate-800">Detail Mahasantri</h1>
                        <p class="text-sm text-slate-400">Informasi lengkap data mahasantri, orangtua, dan dokumen.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('mahasantri.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 active:scale-95">
                        <x-heroicon-s-arrow-left class="h-4 w-4" />
                        Kembali
                    </a>
                    <button type="button" onclick="editModal.showModal()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95">
                        <x-heroicon-s-pencil-square class="h-4 w-4" />
                        Edit
                    </button>
                    <button type="button" onclick="openConfirmModal('/mahasantri/{{ $m->id_mahasantri }}', '{{ e($m->nama_lengkap) }}')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-rose-700 active:scale-95">
                        <x-heroicon-s-trash class="h-4 w-4" />
                        Hapus
                    </button>
                </div>
            </div>

            {{-- ── Data Pribadi ──────────────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-700">Data Pribadi</h2>
                </div>
                <div class="p-5">
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">ID Mahasantri</dt>
                            <dd class="mt-1">
                                <code class="rounded bg-black/[0.05] px-1.5 py-0.5 text-xs font-medium text-black">{{ $m->id_mahasantri }}</code>
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">Nama Lengkap</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $m->nama_lengkap }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">NIK</dt>
                            <dd class="mt-1 text-sm text-slate-700">{{ $m->nik ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">NISN</dt>
                            <dd class="mt-1 text-sm text-slate-700">{{ $m->nisn ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">Jenis Kelamin</dt>
                            <dd class="mt-1">
                                @if($m->jenis_kelamin === 'L')
                                    <span class="rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-200">Laki-laki</span>
                                @elseif($m->jenis_kelamin === 'P')
                                    <span class="rounded-md bg-pink-50 px-2 py-0.5 text-xs font-medium text-pink-700 ring-1 ring-pink-200">Perempuan</span>
                                @else
                                    <span class="text-sm text-slate-400">-</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">Tempat Lahir</dt>
                            <dd class="mt-1 text-sm text-slate-700">{{ $m->tempat_lahir ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">Tanggal Lahir</dt>
                            <dd class="mt-1 text-sm text-slate-700">
                                {{ $m->tanggal_lahir ? (is_string($m->tanggal_lahir) ? $m->tanggal_lahir : $m->tanggal_lahir->translatedFormat('d F Y')) : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">Status</dt>
                            <dd class="mt-1">
                                @switch($m->status)
                                    @case('Pendaftar Baru')
                                        <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">Pendaftar Baru</span>
                                        @break
                                    @case('Terverifikasi')
                                        <span class="rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-sky-200">Terverifikasi</span>
                                        @break
                                    @case('Lulus')
                                        <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Lulus</span>
                                        @break
                                    @case('Tidak Lulus')
                                        <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200">Tidak Lulus</span>
                                        @break
                                    @default
                                        <span class="text-sm text-slate-700">{{ $m->status }}</span>
                                @endswitch
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">Tanggal Daftar</dt>
                            <dd class="mt-1 text-sm text-slate-700">
                                {{ $m->tanggal_daftar ? (is_string($m->tanggal_daftar) ? $m->tanggal_daftar : $m->tanggal_daftar->translatedFormat('d F Y')) : '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- ── Data Orangtua / Wali ─────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-700">Data Orangtua / Wali</h2>
                </div>
                <div class="p-5">
                    @if($m->orangtuas && $m->orangtuas->count() > 0)
                        <div class="space-y-3">
                            @foreach($m->orangtuas as $ort)
                                <div class="rounded-lg border border-slate-200 bg-white p-4">
                                    <div class="mb-3">
                                        <span class="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                            {{ $ort->tipe_hubungan }}
                                        </span>
                                    </div>
                                    <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-3">
                                        <div>
                                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">Nama Lengkap</dt>
                                            <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $ort->nama_lengkap }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">Pekerjaan</dt>
                                            <dd class="mt-0.5 text-sm text-slate-700">{{ $ort->pekerjaan ?? '-' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">No. WhatsApp</dt>
                                            <dd class="mt-0.5">
                                                @if($ort->no_wa)
                                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $ort->no_wa) }}" target="_blank"
                                                       class="inline-flex items-center gap-1 text-sm font-medium text-emerald-600 hover:text-emerald-700">
                                                        <x-heroicon-s-phone class="h-3.5 w-3.5" />
                                                        {{ $ort->no_wa }}
                                                    </a>
                                                @else
                                                    <span class="text-sm text-slate-400">-</span>
                                                @endif
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <x-heroicon-s-user-group class="h-10 w-10 text-slate-300" />
                            <p class="mt-2 text-sm font-medium text-slate-500">Belum ada data orangtua</p>
                            <p class="text-xs text-slate-400">Data orangtua/wali belum ditambahkan untuk mahasantri ini.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Data Dokumen ──────────────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-700">Dokumen</h2>
                </div>
                <div class="p-5">
                    @if($m->berkas && $m->berkas->count() > 0)
                        <div class="overflow-hidden rounded-lg border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tipe Dokumen</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Unduh</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Verifikasi</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tanggal Upload</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach($m->berkas as $doc)
                                        <tr class="transition hover:bg-slate-50" data-berkas-id="{{ $doc->id_berkas }}">
                                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700">
                                                <div class="flex items-center gap-2">
                                                    <x-heroicon-s-document-text class="h-4 w-4 text-slate-400" />
                                                    {{ $doc->tipe_dokumen }}
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                @switch($doc->download_status)
                                                    @case('success')
                                                        <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Berhasil</span>
                                                        @break
                                                    @case('processing')
                                                        <span class="rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-sky-200">Mengunduh...</span>
                                                        @break
                                                    @case('failed')
                                                        <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200">Gagal</span>
                                                        @break
                                                    @default
                                                        <span class="rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-500 ring-1 ring-slate-200">Menunggu</span>
                                                @endswitch
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                @if($doc->is_valid)
                                                    <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Terverifikasi</span>
                                                @else
                                                    <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">Belum Verifikasi</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500">
                                                {{ $doc->tanggal_upload ? (is_string($doc->tanggal_upload) ? $doc->tanggal_upload : $doc->tanggal_upload->translatedFormat('d F Y')) : '-' }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    @if($doc->download_status === 'success' && $doc->file_path)
                                                        <a href="{{ route('berkas.download', $doc->id_berkas) }}"
                                                            class="inline-flex items-center justify-center rounded-md p-2 text-emerald-600 transition hover:bg-emerald-50"
                                                            title="Unduh">
                                                            <x-heroicon-s-arrow-down-tray class="h-4 w-4" />
                                                        </a>
                                                    @endif
                                                    @if($doc->download_status === 'failed')
                                                        <button type="button"
                                                            @click="retryDownload('{{ $doc->id_berkas }}')"
                                                            class="inline-flex items-center justify-center rounded-md p-2 text-amber-600 transition hover:bg-amber-50"
                                                            title="Ulangi">
                                                            <x-heroicon-s-arrow-path class="h-4 w-4" />
                                                        </button>
                                                    @endif
                                                    @if($doc->download_status === 'success' && $doc->file_path)
                                                        <button type="button"
                                                            @click="openPreview(
                                                                {{ json_encode(route('berkas.preview', $doc->id_berkas)) }},
                                                                {{ json_encode($doc->tipe_dokumen) }},
                                                                {{ json_encode($doc->id_berkas) }},
                                                                {{ $doc->is_valid ? 'true' : 'false' }},
                                                                {{ json_encode($m->nik) }},
                                                                {{ json_encode($m->nisn) }},
                                                                {{ json_encode($m->tempat_lahir) }},
                                                                {{ json_encode($m->tanggal_lahir ? (is_string($m->tanggal_lahir) ? $m->tanggal_lahir : $m->tanggal_lahir->format('Y-m-d')) : '') }}
                                                            )"
                                                            class="inline-flex items-center justify-center rounded-md p-2 text-indigo-600 transition hover:bg-indigo-50"
                                                            title="Lihat">
                                                            <x-heroicon-s-eye class="h-4 w-4" />
                                                        </button>
                                                    @elseif($doc->download_status !== 'success')
                                                        <span class="inline-flex items-center justify-center rounded-md p-2 text-slate-400"
                                                            title="Preview tidak tersedia">
                                                            <x-heroicon-s-eye-slash class="h-4 w-4" />
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <x-heroicon-s-document class="h-10 w-10 text-slate-300" />
                            <p class="mt-2 text-sm font-medium text-slate-500">Belum ada dokumen</p>
                            <p class="text-xs text-slate-400">Dokumen belum diunggah untuk mahasantri ini.</p>
                        </div>
                    @endif
                </div>
            </div>

        </section>
    </x-ui.sidebar>

    {{-- ── Modal: Edit ────────────────────────────────────────────────── --}}
    <x-ui.modal-form id="editModal" title="Edit Mahasantri">
        <x-slot name="body">
            <form id="editModal-form" action="{{ route('mahasantri.update', $m->id_mahasantri) }}" method="POST" class="space-y-3">
                @csrf
                @method('PUT')

                {{-- ID readonly --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold text-sm">ID Mahasantri</span>
                    </label>
                    <input type="text" value="{{ $m->id_mahasantri }}" class="input input-bordered input-sm bg-base-200" disabled />
                    <label class="label">
                        <span class="label-text-alt text-base-content/50">ID tidak dapat diubah</span>
                    </label>
                </div>

                <x-ui.form-input name="nama_lengkap" label="Nama Lengkap" placeholder="Masukkan nama lengkap" maxlength="35" required value="{{ old('nama_lengkap', $m->nama_lengkap) }}" />
                <x-ui.form-input name="nik"          label="NIK"           placeholder="16 digit NIK"        maxlength="16" value="{{ old('nik', $m->nik) }}" />
                <x-ui.form-input name="nisn"         label="NISN"          placeholder="10 digit NISN"       maxlength="10" value="{{ old('nisn', $m->nisn) }}" />
                <x-ui.form-select name="jenis_kelamin" label="Jenis Kelamin" :options="$jenisKelaminOptions" placeholder="Pilih jenis kelamin" :selected="old('jenis_kelamin', $m->jenis_kelamin)" />
                <x-ui.form-input name="tempat_lahir" label="Tempat Lahir"  placeholder="Masukkan tempat lahir" maxlength="50" value="{{ old('tempat_lahir', $m->tempat_lahir) }}" />
                <x-ui.form-input name="tanggal_lahir" label="Tanggal Lahir" type="date" value="{{ old('tanggal_lahir', $m->tanggal_lahir ? (is_string($m->tanggal_lahir) ? $m->tanggal_lahir : $m->tanggal_lahir->format('Y-m-d')) : '') }}" />
                <x-ui.form-select name="status" label="Status" :options="$statusOptions" :selected="old('status', $m->status)" />
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
        body-text="Apakah Anda yakin ingin menghapus data mahasantri"
        confirm-label="Hapus"
    />

    {{-- ── Modal: Preview Dokumen (Iframe) ─────────────────────────────── --}}
    <dialog id="previewModal" class="modal" onclick="if(event.target === this) this.close();">
        <div class="modal-box max-w-5xl w-full">
            {{-- Header: Judul + Toggle Status + Close --}}
            <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
                <div class="flex items-center gap-4">
                    <h3 class="text-lg font-semibold text-slate-800" x-text="previewTitle"></h3>
                    <div class="flex items-center gap-2">
                        @if(auth()->user()->jabatan === 'Panitia')
                        {{-- Toggle Switch (hanya Panitia) --}}
                        <button type="button"
                            @click="togglePreviewStatus()"
                            :class="previewTempIsValid ? 'bg-emerald-500' : 'bg-gray-300'"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                            <span :class="previewTempIsValid ? 'translate-x-5' : 'translate-x-0'"
                                class="inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                        </button>
                        @endif
                        {{-- Status Label --}}
                        <span x-show="previewTempIsValid"
                            class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Terverifikasi</span>
                        <span x-show="!previewTempIsValid"
                            class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">Belum Verifikasi</span>
                    </div>
                </div>
                <button type="button" class="btn btn-ghost btn-sm btn-square" onclick="document.getElementById('previewModal').close()">
                    <x-heroicon-s-x-mark class="h-5 w-5" />
                </button>
            </div>

            {{-- Iframe Preview --}}
            <iframe
                :src="previewUrl"
                class="w-full rounded-lg border border-slate-200"
                style="height: 60vh;"
                frameborder="0"
                allowfullscreen
            ></iframe>

            @if(auth()->user()->jabatan === 'Panitia')
            {{-- ── Form Data Mahasantri (Inline Edit) — hanya Panitia ───── --}}
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="mb-2 flex items-center gap-2">
                    <x-heroicon-s-pencil class="h-4 w-4 text-slate-500" />
                    <span class="text-sm font-semibold text-slate-700">Data Pribadi (edit langsung)</span>
                    <span class="text-xs text-slate-400">Cocokkan dengan dokumen di atas</span>
                </div>
                <div class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- NIK --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-0.5">NIK</label>
                        <input type="text" x-model="previewNik" maxlength="16" placeholder="16 digit NIK"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-400 focus:outline-none focus:ring-1 focus:ring-emerald-400">
                    </div>
                    {{-- NISN --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-0.5">NISN</label>
                        <input type="text" x-model="previewNisn" maxlength="10" placeholder="10 digit NISN"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-400 focus:outline-none focus:ring-1 focus:ring-emerald-400">
                    </div>
                    {{-- Tempat Lahir --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-0.5">Tempat Lahir</label>
                        <input type="text" x-model="previewTempatLahir" maxlength="50" placeholder="Tempat lahir"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-400 focus:outline-none focus:ring-1 focus:ring-emerald-400">
                    </div>
                    {{-- Tanggal Lahir --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-0.5">Tanggal Lahir</label>
                        <input type="date" x-model="previewTanggalLahir"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-800 focus:border-emerald-400 focus:outline-none focus:ring-1 focus:ring-emerald-400">
                    </div>
                </div>
            </div>

            {{-- Footer: Tombol Simpan (hanya Panitia) --}}
            <div class="mt-4 flex items-center justify-end">
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('previewModal').close()">
                        Tutup
                    </button>
                    <button type="button" @click="saveBerkasStatus()"
                        :disabled="saving"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                    </button>
                </div>
            </div>
            @endif
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

</div>

{{-- Override openConfirmModal for delete modal --}}
<script>
    function openConfirmModal(action, name) {
        document.getElementById('deleteModal-name').textContent = name;
        document.getElementById('deleteModal-form').action = action;
        document.getElementById('deleteModal').showModal();
    }
</script>

@endsection
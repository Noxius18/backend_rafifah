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

    // ── Data dokumen untuk carousel preview slider ────────────────────────────────────────
    $previewDocs = $m->berkas
        ->filter(fn($b) => $b->download_status === 'success' && $b->file_path)
        ->values()
        ->map(fn($b) => [
            'url'        => route('berkas.preview', $b->id_berkas),
            'title'      => $b->tipe_dokumen,
            'id'         => $b->id_berkas,
            'isValid'    => $b->is_valid,
            'isImage'    => $b->tipe_dokumen === 'Pas Foto',
            'uploadDate' => $b->tanggal_upload
                ? (is_string($b->tanggal_upload) ? $b->tanggal_upload : $b->tanggal_upload->translatedFormat('d F Y'))
                : '-',
        ])
        ->toJson();
@endphp

<div x-data="{
    toast: { show: false, message: '', type: 'success', timer: null },
    showToast(message, type = 'success') {
        if (this.toast.timer) clearTimeout(this.toast.timer);
        Object.assign(this.toast, { message, type, show: true });
        this.toast.timer = setTimeout(() => this.toast.show = false, 4000);
    },

    // ── Preview Dokumen (Iframe Modal) ─────────────────────────────────
    previewDocs: [],
    previewDocIndex: 0,
    saving: false,
    // Field data mahasantri untuk diisi di modal preview
    previewNik: '',
    previewNisn: '',
    get previewDoc() {
        return this.previewDocs[this.previewDocIndex] || {};
    },
    get previewUrl() {
        return this.previewDoc.url || '';
    },
    get previewTitle() {
        return this.previewDoc.title || '';
    },
    get previewTotal() {
        return this.previewDocs.length;
    },
    get isImageDoc() {
        return this.previewDoc.isImage || false;
    },
    get currentLocalIsValid() {
        return this.previewDoc.localIsValid ?? this.previewDoc.isValid ?? false;
    },
    get hasChanges() {
        return this.previewDocs.some(doc => (doc.localIsValid ?? doc.isValid) !== doc.isValid);
    },
    openPreview(docs, index, nik, nisn) {
        // Inisialisasi localIsValid untuk setiap dokumen jika belum ada
        this.previewDocs = docs.map(d => ({ ...d, localIsValid: d.localIsValid ?? d.isValid }));
        this.previewDocIndex = index;
        this.previewNik = nik || '';
        this.previewNisn = nisn || '';
        document.getElementById('previewModal').showModal();
    },
    prevDoc() {
        if (this.previewDocIndex > 0) {
            this.previewDocIndex--;
        }
    },
    nextDoc() {
        if (this.previewDocIndex < this.previewDocs.length - 1) {
            this.previewDocIndex++;
        }
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

    // ── Toggle (simpan state ke doc.localIsValid) ──────────────────────
    togglePreviewStatus() {
        const doc = this.previewDocs[this.previewDocIndex];
        if (doc) {
            doc.localIsValid = !(doc.localIsValid ?? doc.isValid ?? false);
        }
    },

    // ── Simpan SEMUA perubahan sekaligus ───────────────────────────────
    async saveBerkasStatus() {
        const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
        if (!csrfToken) { this.showToast('CSRF token tidak ditemukan', 'error'); return; }

        // Filter dokumen yang berubah
        const changed = this.previewDocs.filter(doc => (doc.localIsValid ?? doc.isValid) !== doc.isValid);

        if (changed.length === 0 && !this.previewNik && !this.previewNisn) {
            this.showToast('Tidak ada perubahan yang perlu disimpan', 'error');
            return;
        }

        this.saving = true;

        try {
            // Kirim semua perubahan secara paralel
            const promises = [];

            // Loop setiap dokumen yang berubah
            for (const doc of changed) {
                const body = { is_valid: doc.localIsValid ?? false };

                // Data mahasantri hanya dikirim sekali (bersama dokumen pertama)
                if (doc === changed[0]) {
                    body.nik = this.previewNik || null;
                    body.nisn = this.previewNisn || null;
                }

                promises.push(
                    fetch(`/berkas/${doc.id}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(body),
                    }).then(r => r.json())
                );
            }

            // Jika tidak ada dokumen berubah tapi data mahasantri berubah
            if (changed.length === 0 && (this.previewNik || this.previewNisn)) {
                promises.push(
                    fetch(`/berkas/${this.previewDocs[0]?.id}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            is_valid: this.previewDocs[0]?.isValid ?? false,
                            nik: this.previewNik || null,
                            nisn: this.previewNisn || null,
                        }),
                    }).then(r => r.json())
                );
            }

            const results = await Promise.all(promises);
            const allOk = results.every(r => r.message);

            if (allOk) {
                // Sinkronkan state lokal dengan server
                for (const doc of changed) {
                    doc.isValid = doc.localIsValid;
                }
                this.showToast('Semua perubahan berhasil disimpan');
                setTimeout(() => window.location.reload(), 500);
            } else {
                this.showToast('Beberapa perubahan gagal disimpan', 'error');
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
                   @if(auth()->user()->jabatan === 'Panitia')
    <button type="button" onclick="editModal.showModal()"
        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95">
        <x-heroicon-s-pencil-square class="h-4 w-4" />
        Edit
    </button>
    <button type="button" onclick="openConfirmModal('/mahasantri/{{ $mahasantri->id_mahasantri }}', '{{ e($mahasantri->nama_lengkap) }}')"
        class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-rose-700 active:scale-95">
        <x-heroicon-s-trash class="h-4 w-4" />
        Hapus
    </button>
@endif
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
                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">Email</dt>
                            <dd class="mt-1 text-sm text-slate-700">{{ $m->email ?? '-' }}</dd>
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
                            <dt class="text-xs font-medium text-slate-400 uppercase tracking-wider">Alamat</dt>
                            <dd class="mt-1 text-sm text-slate-700">{{ $m->alamat ?? '-' }}</dd>
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
                                                        @php
                                                            $clickedIndex = $m->berkas
                                                                ->filter(fn($b) => $b->download_status === 'success' && $b->file_path)
                                                                ->values()
                                                                ->search(fn($b) => $b->id_berkas === $doc->id_berkas);
                                                        @endphp
                                                        <button type="button"
                                                            @click="openPreview(
                                                                {{ $previewDocs }},
                                                                {{ $clickedIndex !== false ? $clickedIndex : 0 }},
                                                                {{ json_encode($m->nik) }},
                                                                {{ json_encode($m->nisn) }}
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
                <x-ui.form-input name="alamat" label="Alamat Tempat Tinggal" placeholder="Masukkan alamat" maxlength="255" value="{{ old('alamat', $m->alamat) }}" />
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

    {{-- ── Modal: Preview Dokumen ─────────────────────────────────── --}}
    <dialog id="previewModal" class="modal" onclick="if(event.target === this) this.close();">
        <div class="modal-box max-w-5xl w-full">
            {{-- Header: Judul + Close --}}
            <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
                <div class="flex items-center gap-3">
                    <h3 class="text-base font-semibold text-slate-800" x-text="previewTitle"></h3>
                </div>
                <button type="button" class="btn btn-ghost btn-sm btn-square" onclick="document.getElementById('previewModal').close()">
                    <x-heroicon-s-x-mark class="h-5 w-5" />
                </button>
            </div>

            {{-- Grid: kiri (preview + carousel) lebih besar, kanan (form) lebih ringkas --}}
            <div class="lg:grid lg:grid-cols-3 gap-4">
                {{-- KIRI: Preview + Carousel — col-span-2 --}}
                <div class="lg:col-span-2 flex flex-col">
                    {{-- Preview Area: <img> untuk image, <iframe> untuk PDF --}}
                    <template x-if="isImageDoc">
                        <div class="flex items-center justify-center w-full rounded-lg border border-slate-200 bg-slate-50 p-4 min-h-[40vh]">
                            <img :src="previewUrl" :alt="previewTitle" class="max-w-full max-h-[45vh] object-contain rounded shadow-sm" />
                        </div>
                    </template>
                    <template x-if="!isImageDoc">
                        <iframe
                            :src="previewUrl"
                            class="w-full rounded-lg border border-slate-200 min-h-[40vh]"
                            frameborder="0"
                            allowfullscreen
                        ></iframe>
                    </template>

                    {{-- Carousel Thumbnails (tetap utuh) --}}
                    <template x-if="previewTotal > 1">
                        <div class="mt-3">
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                <template x-for="(doc, idx) in previewDocs" :key="doc.id">
                                    <button type="button" @click="previewDocIndex = idx"
                                        class="relative flex flex-col items-center gap-1 rounded-lg border-2 p-1.5 transition hover:bg-slate-50 min-w-[64px]"
                                        :class="idx === previewDocIndex ? 'border-emerald-500 bg-emerald-50' : (doc.isValid !== doc.localIsValid ? 'border-amber-400 bg-amber-50' : 'border-slate-200')">
                                        <template x-if="doc.isImage">
                                            <img :src="doc.url" class="h-10 w-10 rounded object-cover" />
                                        </template>
                                        <template x-if="!doc.isImage">
                                            <div class="flex h-10 w-10 items-center justify-center rounded bg-slate-100">
                                                <x-heroicon-s-document-text class="h-5 w-5 text-slate-400" />
                                            </div>
                                        </template>
                                        <span class="text-[10px] font-medium text-slate-600 text-center leading-tight" x-text="doc.title"></span>
                                    </button>
                                </template>
                            </div>
                            <div class="flex items-center justify-center gap-3 mt-2">
                                <button type="button" @click="prevDoc" :disabled="previewDocIndex === 0"
                                    class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium transition disabled:opacity-30 disabled:cursor-not-allowed"
                                    :class="previewDocIndex > 0 ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'text-slate-400'">
                                    <x-heroicon-s-chevron-left class="h-3.5 w-3.5" />
                                    Sebelumnya
                                </button>
                                <span class="text-xs font-medium text-slate-500 min-w-[3rem] text-center" x-text="`${previewDocIndex + 1} / ${previewTotal}`"></span>
                                <button type="button" @click="nextDoc" :disabled="previewDocIndex === previewTotal - 1"
                                    class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium transition disabled:opacity-30 disabled:cursor-not-allowed"
                                    :class="previewDocIndex < previewTotal - 1 ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'text-slate-400'">
                                    Selanjutnya
                                    <x-heroicon-s-chevron-right class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- KANAN: Panel Verifikasi (hanya Panitia) — 1 kolom --}}
                @if(auth()->user()->jabatan === 'Panitia')
                <div class="flex flex-col justify-between bg-white rounded-lg border border-slate-200 p-4 min-h-[40vh]">
                    <div>
                        {{-- Header panel --}}
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-s-clipboard-document-check class="h-4 w-4 text-emerald-600" />
                            <span class="text-sm font-semibold text-slate-700">Verifikasi Dokumen</span>
                        </div>

                        {{-- Metadata tanggal upload --}}
                        <div class="mb-4 flex items-center gap-1.5 text-xs text-slate-500 bg-slate-50 rounded-md px-3 py-2 border border-slate-100">
                            <x-heroicon-s-calendar-days class="h-3.5 w-3.5" />
                            <span>Upload:</span>
                            <span class="font-medium text-slate-700" x-text="previewDoc.uploadDate || '-'"></span>
                        </div>

                        {{-- Toggle status --}}
                        <div class="mb-4 p-3 rounded-lg border" :class="currentLocalIsValid ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider" :class="currentLocalIsValid ? 'text-emerald-700' : 'text-amber-700'" x-text="currentLocalIsValid ? 'Terverifikasi' : 'Belum Verifikasi'"></span>
                                <button type="button"
                                    @click="togglePreviewStatus()"
                                    :class="currentLocalIsValid ? 'bg-emerald-500' : 'bg-gray-300'"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                                    <span :class="currentLocalIsValid ? 'translate-x-5' : 'translate-x-0'"
                                        class="inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                </button>
                            </div>
                        </div>

                        {{-- Form Data Pribadi --}}
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 mb-2">
                                <x-heroicon-s-identification class="h-3.5 w-3.5 text-slate-400" />
                                <span class="text-xs font-semibold text-slate-600">Data Pribadi</span>
                                <span class="text-[10px] text-slate-400">Cocokkan dengan dokumen</span>
                            </div>

                            {{-- NIK --}}
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">NIK</label>
                                <div class="relative">
                                    <input type="text" x-model="previewNik" maxlength="16" placeholder="16 digit NIK"
                                        @input="previewNik = previewNik.replace(/\D/g, '').slice(0, 16)"
                                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 pr-12 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-400 focus:outline-none focus:ring-1 focus:ring-emerald-400">
                                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-medium text-slate-400" x-text="previewNik.length + '/16'"></span>
                                </div>
                            </div>
                            {{-- NISN --}}
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">NISN</label>
                                <div class="relative">
                                    <input type="text" x-model="previewNisn" maxlength="10" placeholder="10 digit NISN"
                                        @input="previewNisn = previewNisn.replace(/\D/g, '').slice(0, 10)"
                                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 pr-12 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-400 focus:outline-none focus:ring-1 focus:ring-emerald-400">
                                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-medium text-slate-400" x-text="previewNisn.length + '/10'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Simpan — sticky di bagian bawah --}}
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-end">
                        <button type="button" @click="saveBerkasStatus()"
                            :disabled="saving"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed w-full justify-center">
                            <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                        </button>
                    </div>
                </div>
                @endif
            </div>
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

@extends('layouts.app')

@section('content')

@php
    // Helper: pastikan URL memiliki protocol (https://)
    $formatLinkZoom = function($link) {
        if (!$link) return null;
        if (!preg_match('#^https?://#i', $link)) {
            return 'https://' . $link;
        }
        return $link;
    };

    $gelombangList = [1 => 'Gelombang 1', 2 => 'Gelombang 2'];

    $isKetuaPanitia = auth()->user()->jabatan === 'Ketua Panitia';
    $isPanitia = auth()->user()->jabatan === 'Panitia';

    // Kolom dibalikin komplit
    $columns = [
        ['label' => 'ID',               'field' => 'id_jadwal',    'html' => 'id_html'],
        ['label' => 'Mahasantri',       'field' => 'mhs_nama',     'html' => 'mhs_html'],
        ['label' => 'Gelombang',        'field' => 'gelombang',    'html' => 'gelombang_html'],
        ['label' => 'Tanggal',          'field' => 'tanggal',      'html' => 'tgl_html'],
        ['label' => 'Jam',              'field' => 'jam',          'html' => 'jam_html'],
        ['label' => 'Status',           'field' => 'status_jadwal', 'html' => 'status_konfirmasi_html'],
        ['label' => 'Hasil',            'field' => 'hasil',        'html' => 'hasil_html'],
        ['label' => 'Link Zoom',        'field' => 'link_zoom',    'html' => 'link_html', 'class' => 'hidden lg:table-cell'],
        ['label' => 'Aksi',             'field' => 'id_jadwal',    'html' => 'aksi_html', 'class' => 'text-right'],
    ];

    $rows = $jadwals->map(function($j) use ($isKetuaPanitia, $isPanitia, $formatLinkZoom, $nilaiPerAspekByJadwal) {
        // Gunakan hasilTes yang sudah di-eager-load
        $hasil = $j->hasilTes;
        $statusHasil = $hasil ? $hasil->status : 'Belum Tes';

        // Ambil nilai per aspek dari $nilaiPerAspekByJadwal yang sudah disiapkan controller
        $nilaiPerAspek = $nilaiPerAspekByJadwal[$j->id_jadwal] ?? [];
        $nilaiBacaan = $nilaiPerAspek['Bacaan Al-Quran']['nilai'] ?? '';
        $nilaiTajwid = $nilaiPerAspek['Tajwid/Tahsin']['nilai'] ?? '';
        $nilaiHafalan = $nilaiPerAspek['Hafalan']['nilai'] ?? '';
        $nilaiWawancara = $nilaiPerAspek['Wawancara']['nilai'] ?? '';
        $catatanPenguji = $nilaiPerAspek['Bacaan Al-Quran']['catatan'] 
            ?? $nilaiPerAspek['Tajwid/Tahsin']['catatan'] 
            ?? $nilaiPerAspek['Hafalan']['catatan'] 
            ?? $nilaiPerAspek['Wawancara']['catatan'] 
            ?? '';

        // Buat object data nilai lengkap untuk dikirim ke modal
        $dataNilai = [
            'id_hasil' => $hasil?->id_hasil,
            'status' => $statusHasil,
            'nilai_bacaan_al_quran' => $nilaiBacaan,
            'nilai_tajwid_tahsin' => $nilaiTajwid,
            'nilai_hafalan' => $nilaiHafalan,
            'nilai_wawancara' => $nilaiWawancara,
            'catatan_penguji' => $catatanPenguji,
        ];
        $hasilJson = htmlspecialchars(json_encode($dataNilai), ENT_QUOTES, 'UTF-8');
        
        $aksiHtml = "<div class='flex items-center justify-end gap-0.5'>";

        // 1. TOMBOL DETAIL NILAI — arahkan ke halaman nilai detail
        $aksiHtml .= "<a href='" . route('seleksi.nilai', $j->id_jadwal) . "' class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-indigo-600 hover:bg-indigo-50' title='Detail Nilai'>
                        <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'/></svg>
                      </a>";

        // 2. TOMBOL EDIT (Panitia, untuk semua status jadwal kecuali Dibatalkan/Rescheduled)
        if ($isPanitia && $j->status_jadwal !== 'Dibatalkan' && $j->status_jadwal !== 'Rescheduled') {
            $aksiHtml .= "<button type='button' onclick=\"openEditModal({ id: '{$j->id_jadwal}', jam: '" . ($j->jam ? \Carbon\Carbon::parse($j->jam)->format('H:i') : '') . "', link_zoom: '" . e($j->link_zoom ?? '') . "' })\" class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-indigo-600 hover:bg-indigo-50' title='Edit'><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10'/></svg></button>";
        }
        $aksiHtml .= "</div>";

        // Status jadwal badge (gunakan status_jadwal dari model)
        $statusJadwal = $j->status_jadwal ?? 'Menunggu';
        $statusJadwalHtml = match($statusJadwal) {
            'Menunggu' => "<span class='rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200'>⏳ Menunggu</span>",
            'Disetujui' => "<span class='rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200'>✅ Disetujui</span>",
            'Revisi' => "<span class='rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200'>❌ Perlu Revisi</span>",
            'Dibatalkan' => "<span class='rounded-md bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-gray-200'>🚫 Dibatalkan</span>",
            'Rescheduled' => "<span class='rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-200'>🔄 Dijadwalkan Ulang</span>",
            default => "<span class='text-xs text-slate-500'>{$statusJadwal}</span>",
        };

        // Catatan perubahan dari Ketua Panitia — ditampilkan di modal edit, bukan di baris tabel

        return [
            'id_jadwal'   => $j->id_jadwal,
            'gelombang'   => $j->mahasantri ? \App\Models\User::extractGelombangNama($j->mahasantri->id_mahasantri) : '-',
            'status_jadwal' => $statusJadwal,
            'search'      => strtolower("{$j->id_jadwal} {$j->mahasantri?->nama_lengkap} {$j->tanggal} {$statusJadwal}"),
            'id_html'     => "<code class='rounded bg-black/[0.05] px-1.5 py-0.5 text-xs text-black'>{$j->id_jadwal}</code>",
            'mhs_html'    => $j->mahasantri ? "<span class='font-medium text-black'>" . e($j->mahasantri->nama_lengkap) . "</span><br><span class='text-[10px] text-black/60'>" . e($j->mahasantri->id_mahasantri) . "</span>" : "<span class='text-black/50 text-xs'>-</span>",
            'gelombang_html' => $j->mahasantri ? "<span class='rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-purple-200'>" . e(\App\Models\User::extractGelombangNama($j->mahasantri->id_mahasantri)) . "</span>" : "<span class='text-slate-400'>-</span>",
            'tgl_html'    => "<span class='text-xs text-black'>" . \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') . "</span>",
            'jam_html'    => $j->jam ? "<span class='rounded-md bg-slate-50 px-2 py-0.5 text-xs font-mono font-medium text-slate-600 ring-1 ring-slate-200'>" . \Carbon\Carbon::parse($j->jam)->format('H:i') . "</span>" : "<span class='text-slate-400 text-xs'>-</span>",
            'status_konfirmasi_html' => $statusJadwalHtml,
            'hasil_html'  => match($statusHasil) {
                'Lulus' => "<span class='rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200'>✅ Lulus</span>",
                'Tidak Lulus' => "<span class='rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200'>❌ Tidak Lulus</span>",
                'Pertimbangan' => "<span class='rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200'>⚠️ Pertimbangan</span>",
                default => "<span class='rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-400 ring-1 ring-slate-200'>⏳ Belum Tes</span>",
            },
            'link_html'   => $j->link_zoom ? "<a href='" . e($formatLinkZoom($j->link_zoom)) . "' target='_blank' class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-blue-600 hover:bg-blue-50' title='Buka Zoom'><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9A2.25 2.25 0 0013.5 5.25h-9A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75z'/></svg></a>" : "<span class='text-black/50 text-xs'>-</span>",
            'aksi_html'   => $aksiHtml,
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
    filterGelombang: '',
    reviewStep: 'info', // 'info' | 'reject_form'
    rejectNote: '',
    openReviewModal() {
        this.reviewStep = 'info';
        this.rejectNote = '';
        document.getElementById('reviewModal').showModal();
    },
    showRejectForm() {
        this.reviewStep = 'reject_form';
    },
    backToInfo() {
        this.reviewStep = 'info';
    },
    submitApprove() {
        document.getElementById('approveSemuaForm').submit();
    },
    submitReject() {
        if (this.rejectNote.trim()) {
            document.getElementById('rejectNoteInput').value = this.rejectNote;
            document.getElementById('rejectSemuaForm').submit();
        }
    },
    // Edit Jadwal Revisi modal state
    editRevisi: {
        selectedTanggal: '',
        jamMulai: '',
        interval: 30,
        linkZoom: '',
        penguji: {
            penguji_bacaan_al_quran: '',
            penguji_tajwid_tahsin: '',
            penguji_hafalan: '',
            penguji_wawancara: '',
        },
        open(data) {
            this.selectedTanggal = data.tanggal;
            this.jamMulai = data.jam_mulai;
            this.interval = data.interval;
            this.linkZoom = data.link_zoom;
            this.penguji.penguji_bacaan_al_quran = data.penguji?.penguji_bacaan_al_quran?.id_panitia || '';
            this.penguji.penguji_tajwid_tahsin = data.penguji?.penguji_tajwid_tahsin?.id_panitia || '';
            this.penguji.penguji_hafalan = data.penguji?.penguji_hafalan?.id_panitia || '';
            this.penguji.penguji_wawancara = data.penguji?.penguji_wawancara?.id_panitia || '';
            document.getElementById('editByDateForm').action = '{{ route('seleksi.update-by-date', ':tanggal') }}'.replace(':tanggal', data.tanggal);
            document.getElementById('editByDateModal').showModal();
        },
    },
}"
x-init="@if(session('success')) showToast('{{ session('success') }}') @endif @if(session('error')) showToast('{{ session('error') }}', 'error') @endif">

    <x-ui.toast />
    <x-ui.sidebar>
        <section class="space-y-6 px-1 py-2">
            <div class="flex items-center justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-1 h-7 w-1 rounded-full bg-emerald-500"></div>
                    <div>
                        <h1 class="text-xl font-semibold text-black">Seleksi & Penilaian</h1>
                        <p class="text-sm text-black">Kelola jadwal seleksi dan penilaian mahasantri.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    
                    @if(($isPanitia || $isKetuaPanitia) && $activeGelombang)
                    <a href="{{ route('laporan.panitia.seleksi', $activeGelombang->id_gelombang ?? $activeGelombang->id) }}" target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100 active:scale-95 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Cetak Laporan
                    </a>
                    @endif

                    @if($isPanitia)
                        @if($totalRevisi > 0)
                        <button type="button" x-on:click="document.getElementById('editByDateSelectModal').showModal()"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3.5 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50 active:scale-95 shadow-sm">
                            <x-heroicon-s-pencil-square class="h-4 w-4" />
                            Edit Jadwal Revisi
                        </button>
                        @endif
                        
                        <button type="button" onclick="document.getElementById('sendBulkModal').showModal()"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 active:scale-95 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            Kirim Hasil Penilaian
                        </button>

                        <button type="button" onclick="document.getElementById('addModal').showModal()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95 shadow-sm">
                            <x-heroicon-s-plus class="h-4 w-4" /> Buat Jadwal
                        </button>
                    @endif

                    @if($isKetuaPanitia)
                        @if($totalMenunggu > 0)
                        <div class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-sm text-amber-700">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                            <span><strong class="font-bold">{{ $totalMenunggu }}</strong> jadwal menunggu persetujuan</span>
                        </div>
                        @endif
                        
                        @if($totalPerluReview > 0)
                        <div class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-sm text-amber-700">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                            <span><strong class="font-bold">{{ $totalPerluReview }}</strong> hasil perlu direview</span>
                        </div>
                        @endif
                        
                        <button type="button" x-on:click="openReviewModal()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95 shadow-sm">
                            <x-heroicon-s-check-circle class="h-4 w-4" />
                            Review & Tindak Lanjut
                        </button>
                    @endif
                    
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-black/20 bg-white">
                <x-ui.data-table :rows="$rows" :columns="$columns" :total="$jadwals->total()" empty-message="Belum ada jadwal" add-label="Buat Jadwal" />
                <x-ui.pagination :paginator="$jadwals" alwaysShow="true" />
            </div>
        </section>
    </x-ui.sidebar>

    <x-ui.modal-form id="addModal" title="Buat Jadwal Seleksi Baru" size="xl">
        <x-slot name="body">
            @if($activeGelombang)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <div class="flex items-start gap-2">
                        <svg class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-blue-800 mb-1">Gelombang Aktif</p>
                            <p class="text-xs text-blue-700">{{ $activeGelombang->nama }} ({{ Carbon\Carbon::parse($activeGelombang->start_date)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($activeGelombang->end_date)->format('d/m/Y') }})</p>
                        </div>
                    </div>
                </div>
            @endif
            <div class="-mr-2 pr-2">
            <div id="form-error" class="hidden bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                <div class="flex items-start gap-2">
                    <svg class="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <p class="text-sm text-red-700 font-medium" id="form-error-message"></p>
                </div>
            </div>
            <form id="addModal-form" action="{{ route('seleksi.store') }}" method="POST" class="space-y-4" onsubmit="return validateGelombangDate(this)">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @if($activeGelombang)
                        <x-ui.form-input name="tanggal" label="Tanggal Seleksi" type="date" required />
                    @else
                        <x-ui.form-input name="tanggal" label="Tanggal Seleksi" type="date" required />
                        <p class="text-xs text-red-600 mt-1">
                            <span class="font-medium">Peringatan:</span> Tidak ada gelombang aktif saat ini.
                        </p>
                    @endif
                    <x-ui.form-input name="jam_mulai" label="Jam Mulai" type="time" required />
                    <x-ui.form-input name="interval" label="Interval (menit)" type="number" value="30" min="5" max="120" required />
                    <x-ui.form-input name="link_zoom" label="Link Zoom" placeholder="https://zoom.us/j/..." />
                </div>
                <div class="border-t border-black/10 pt-3">
                    <p class="mb-2 text-sm font-semibold text-black">Tentukan Penguji Materi</p>
                    <div class="grid grid-cols-1 gap-4">
                        @foreach ($aspekMapping as $aspek => $field)
                            <x-ui.form-select name="penguji_{{ $field }}" :label="$aspek" :options="$panitias->pluck('nama_lengkap', 'id_panitia')->toArray()" placeholder="-- Pilih Panitia --" />
                        @endforeach
                    </div>
                </div>
            </form>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm text-black hover:bg-black/[0.05]" onclick="document.getElementById('addModal').close()">Batal</button>
            <button type="submit" form="addModal-form" class="btn btn-success btn-sm">Simpan</button>
        </x-slot>
    </x-ui.modal-form>

    {{-- MODAL INPUT/LIHAT NILAI (PANITIA/PENGAWAS) --}}

    {{-- MODAL KIRIM EMAIL MASSAL --}}
    <x-ui.modal-form id="sendBulkModal" title="Jadwalkan Surat Kelulusan Massal" size="md">
        <x-slot name="body">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                <div class="flex items-start gap-2">
                    <svg class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-blue-800 mb-1">Informasi Penjadwalan</p>
                        <p class="text-xs text-blue-700">
                            Sistem akan otomatis mendeteksi <strong class="font-bold">Gelombang yang Aktif</strong> saat ini. 
                            Email pengumuman hanya akan dikirimkan kepada mahasantri di gelombang tersebut yang nilainya sudah direview (Lulus/Tidak Lulus).
                        </p>
                    </div>
                </div>
            </div>
            <form id="sendBulkModal-form" action="{{ route('seleksi.send-bulk-results') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <p class="text-sm font-semibold mb-2">Tentukan Waktu Pengiriman Otomatis</p>
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.form-input name="tanggal_kirim" label="Tanggal Kirim" type="date" required />
                        <x-ui.form-input name="jam_kirim" label="Jam Kirim" type="time" required />
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm text-black hover:bg-black/[0.05]" onclick="document.getElementById('sendBulkModal').close()">Batal</button>
            <button type="submit" form="sendBulkModal-form" class="btn bg-emerald-600 hover:bg-emerald-700 text-white btn-sm border-none" onclick="this.innerHTML='<span class=\'loading loading-spinner loading-xs\'></span> Menjadwalkan...'">Jadwalkan Email</button>
        </x-slot>
    </x-ui.modal-form>

    <x-ui.modal-form id="editModal" title="Edit Jadwal Seleksi" size="lg">
        <x-slot name="body">
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 mb-4">
                <div class="flex items-start gap-2">
                    <svg class="h-5 w-5 text-slate-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-slate-700 mb-1">Rentang Gelombang</p>
                        <p class="text-xs text-slate-600">
                            @if($gelombangs->count() > 0)
                                @foreach($gelombangs as $g)
                                    {{ $g->nama }}: {{ Carbon\Carbon::parse($g->start_date)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($g->end_date)->format('d/m/Y') }}@if(!$loop->last) • @endif
                                @endforeach
                            @else
                                Tidak ada gelombang dikonfigurasi
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div id="edit-form-error" class="hidden bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                <div class="flex items-start gap-2">
                    <svg class="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <p class="text-sm text-red-700 font-medium" id="edit-form-error-message"></p>
                </div>
            </div>
            <form id="editModal-form" action="" method="POST" class="space-y-3" onsubmit="return validateEditGelombangDate(this)">
                @csrf @method('PUT')
                <x-ui.form-input name="jam" label="Jam Mulai" type="time" required />
                <x-ui.form-input name="link_zoom" label="Link Zoom" placeholder="https://zoom.us/j/..." />
                <div class="border-t border-slate-100 pt-3">
                    <p class="text-sm text-slate-500">*Tanggal tidak dapat diubah (diatur saat pembuatan jadwal)</p>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm text-black hover:bg-black/[0.05]" onclick="document.getElementById('editModal').close()">Batal</button>
            <button type="submit" form="editModal-form" class="btn btn-success btn-sm">Perbarui</button>
        </x-slot>
    </x-ui.modal-form>

    <x-ui.modal-form id="cancelModal" title="Batalkan Jadwal">
        <x-slot name="body">
            <form id="cancelModal-form" action="" method="POST">
                @csrf
                @method('DELETE')
                <div class="space-y-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold text-sm">Jenis Pembatalan</span></label>
                        <select name="jenis_pembatalan" class="select select-bordered select-sm" required>
                            <option value="">-- Pilih --</option>
                            <option value="Dibatalkan">Dibatalkan</option>
                            <option value="Rescheduled">Dijadwalkan Ulang</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold text-sm">Alasan Pembatalan</span></label>
                        <textarea name="alasan_pembatalan" class="textarea textarea-bordered" rows="3" placeholder="Masukkan alasan pembatalan..." required></textarea>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('cancelModal').close()">Batal</button>
            <button type="submit" form="cancelModal-form" class="btn btn-error btn-sm">Batalkan Jadwal</button>
        </x-slot>
    </x-ui.modal-form>

    <!-- Modal Approve Jadwal (Hanya Ketua Panitia) -->
    <x-ui.modal-form id="approveModal" title="Setujui Jadwal">
        <x-slot name="body">
            <form id="approveModal-form" action="" method="POST">
                @csrf
                <p class="text-sm">Setujui <strong>SEMUA</strong> jadwal di tanggal yang sama?</p>
                <p class="text-xs text-slate-500 mt-2">Setelah disetujui, jadwal tidak dapat diubah kecuali melalui pembatalan.</p>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('approveModal').close()">Batal</button>
            <button type="submit" form="approveModal-form" class="btn btn-success btn-sm">Setujui</button>
        </x-slot>
    </x-ui.modal-form>

    <!-- Modal Reject Jadwal (Hanya Ketua Panitia) -->
    <x-ui.modal-form id="rejectModal" title="Ajukan Perubahan Jadwal">
        <x-slot name="body">
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 text-sm text-amber-700">
                Perubahan akan diterapkan ke <strong>SEMUA</strong> jadwal di tanggal yang sama.
            </div>
            <form id="rejectModal-form" action="" method="POST">
                @csrf
                <div class="space-y-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold text-sm">Alasan Perubahan</span></label>
                        <textarea name="catatan_perubahan" class="textarea textarea-bordered" rows="4" placeholder="Tuliskan alasan perubahan jadwal..." required></textarea>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('rejectModal').close()">Batal</button>
            <button type="submit" form="rejectModal-form" class="btn btn-warning btn-sm">Ajukan Perubahan</button>
        </x-slot>
    </x-ui.modal-form>

    {{-- MODAL BATCH CANCEL (Panitia) --}}
    <x-ui.modal-form id="cancelBulkModal" title="Batalkan Semua Jadwal">
        <x-slot name="body">
            <form id="cancelBulkModal-form" action="{{ route('seleksi.bulk-cancel') }}" method="POST">
                @csrf
                <div class="space-y-3">
                    <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-700">
                        <p class="font-medium">Hanya jadwal yang sudah <strong>Disetujui</strong> yang akan dibatalkan.</p>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold text-sm">Jenis</span></label>
                        <select name="jenis_pembatalan" class="select select-bordered select-sm" required>
                            <option value="">-- Pilih --</option>
                            <option value="Dibatalkan">Dibatalkan</option>
                            <option value="Rescheduled">Dijadwalkan Ulang</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold text-sm">Alasan Pembatalan</span></label>
                        <textarea name="alasan_pembatalan" class="textarea textarea-bordered" rows="3" placeholder="Masukkan alasan..." required></textarea>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('cancelBulkModal').close()">Batal</button>
            <button type="submit" form="cancelBulkModal-form" class="btn btn-error btn-sm">Batalkan Semua</button>
        </x-slot>
    </x-ui.modal-form>

    {{-- MODAL REVIEW & TINDAK LANJUT (Ketua Panitia) --}}
    <x-ui.modal id="reviewModal" size="xl">
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100">
                    <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-800" x-show="reviewStep === 'info'">Review & Tindak Lanjut Jadwal</h3>
                    <h3 class="text-lg font-semibold text-slate-800" x-show="reviewStep === 'reject_form'">Ajukan Perubahan Jadwal</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Ketua Panitia — review data jadwal sebelum memutuskan</p>
                </div>
            </div>
        </x-slot>
        <x-slot name="body">
            {{-- STEP 1: Info Penguji & Ringkasan --}}
            <div x-show="reviewStep === 'info'">
                @if($totalMenunggu > 0)
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 text-sm text-amber-700">
                        <strong class="font-bold">{{ $totalMenunggu }}</strong> jadwal menunggu persetujuan dari <strong class="font-bold">{{ count($jadwalsByTanggal) }}</strong> tanggal berbeda.
                    </div>

                    <div class="space-y-3 max-h-[50vh] overflow-y-auto -mr-2 pr-2">
                        @foreach($jadwalsByTanggal as $group)
                            <div class="border border-slate-200 rounded-lg overflow-hidden">
                                <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex items-center justify-between">
                                    <span class="font-semibold text-sm text-slate-700">
                                        {{ \Carbon\Carbon::parse($group['tanggal'])->format('d/m/Y') }}
                                    </span>
                                    <span class="text-xs text-slate-500">{{ $group['total'] }} jadwal</span>
                                </div>
                                <div class="px-4 py-3 space-y-1.5">
                                    @foreach($group['penguji'] as $aspek => $nama)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-slate-600">{{ $aspek }}</span>
                                            <span class="font-medium text-slate-800">{{ $nama }}</span>
                                        </div>
                                    @endforeach
                                    <div class="border-t border-slate-100 pt-1.5 mt-1.5 flex items-center justify-between text-sm">
                                        <span class="text-slate-500">Penanggung Jawab</span>
                                        <span class="font-medium text-slate-800">{{ $group['penanggung_jawab'] }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="h-12 w-12 mx-auto text-emerald-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-slate-700">✅ Semua jadwal sudah diproses</p>
                        <p class="text-xs text-slate-400 mt-1">Tidak ada jadwal yang menunggu persetujuan.</p>
                    </div>
                @endif
            </div>

            {{-- STEP 2: Form Alasan Perubahan --}}
            <div x-show="reviewStep === 'reject_form'">
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 text-sm text-amber-700">
                    Ajukan perubahan untuk <strong class="font-bold">SEMUA</strong> jadwal yang masih <strong>Menunggu</strong> atau <strong>Revisi</strong>.
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold text-sm">Alasan Perubahan</span></label>
                    <textarea x-model="rejectNote" class="textarea textarea-bordered" rows="4" placeholder="Tuliskan alasan perubahan jadwal..." required></textarea>
                </div>
                <p class="text-xs text-slate-400 mt-2" x-show="rejectNote.length > 0 && rejectNote.length < 5">Alasan minimal 5 karakter.</p>
            </div>

            {{-- Hidden forms --}}
            <form id="approveSemuaForm" action="{{ route('seleksi.approve-all') }}" method="POST" class="hidden">@csrf</form>
            <form id="rejectSemuaForm" action="{{ route('seleksi.reject-all') }}" method="POST" class="hidden">@csrf
                <input id="rejectNoteInput" type="hidden" name="catatan_perubahan" value="" />
            </form>
        </x-slot>
        <x-slot name="footer" class="flex items-center justify-between">
            <template x-if="reviewStep === 'info'">
                <div class="flex items-center gap-2 w-full justify-between">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('reviewModal').close()">Tutup</button>
                    @if($totalMenunggu > 0)
                    <div class="flex items-center gap-2">
                        <button type="button" x-on:click="showRejectForm()" class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-white px-3.5 py-2 text-sm font-medium text-amber-600 transition hover:bg-amber-50 active:scale-95 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
                            Ajukan Perubahan
                        </button>
                        <button type="button" x-on:click="submitApprove()" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95 shadow-sm">
                            <x-heroicon-s-check-circle class="h-4 w-4" />
                            Setujui Semua
                        </button>
                    </div>
                    @endif
                </div>
            </template>
            <template x-if="reviewStep === 'reject_form'">
                <div class="flex items-center gap-2 w-full justify-between">
                    <button type="button" x-on:click="backToInfo()" class="btn btn-ghost btn-sm">Kembali</button>
                    <button type="button" x-on:click="submitReject()" :disabled="!rejectNote.trim() || rejectNote.trim().length < 5"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-amber-700 active:scale-95 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        Kirim Perubahan
                    </button>
                </div>
            </template>
        </x-slot>
    </x-ui.modal>

    {{-- MODAL PILIH TANGGAL REVISI (Panitia) --}}
    <x-ui.modal id="editByDateSelectModal" size="md">
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-100">
                    <x-heroicon-s-pencil-square class="h-5 w-5 text-rose-600" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Pilih Tanggal Revisi</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Pilih tanggal jadwal yang akan diperbaiki</p>
                </div>
            </div>
        </x-slot>
        <x-slot name="body">
            <div class="space-y-2 max-h-[60vh] overflow-y-auto">
                @forelse($jadwalsRevisiByTanggal as $group)
                    <button type="button" x-on:click="editRevisi.open(@js($group)); document.getElementById('editByDateSelectModal').close()"
                        class="w-full text-left p-3 rounded-lg border border-slate-200 hover:border-rose-300 hover:bg-rose-50 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($group['tanggal'])->format('d/m/Y') }}</span>
                                <span class="text-xs text-slate-500 ml-2">{{ $group['total'] }} jadwal</span>
                            </div>
                            <svg class="h-4 w-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                            </svg>
                        </div>
                        @if($group['catatan_perubahan'])
                            <div class="mt-2 text-xs text-rose-600 bg-rose-50 rounded px-2 py-1.5 leading-relaxed">
                                📝 {{ $group['catatan_perubahan'] }}
                            </div>
                        @endif
                    </button>
                @empty
                    <p class="text-sm text-slate-400 text-center py-4">Tidak ada jadwal dengan status Revisi.</p>
                @endforelse
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('editByDateSelectModal').close()">Tutup</button>
        </x-slot>
    </x-ui.modal>

    {{-- MODAL EDIT JADWAL REVISI (Panitia) --}}
    <x-ui.modal id="editByDateModal" size="xl">
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-100">
                    <x-heroicon-s-pencil-square class="h-5 w-5 text-rose-600" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Edit Jadwal Revisi</h3>
                    <p class="text-xs text-slate-500 mt-0.5" x-text="'Tanggal: ' + editRevisi.selectedTanggal"></p>
                </div>
            </div>
        </x-slot>
        <x-slot name="body">
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 text-sm text-amber-700">
                Perubahan akan diterapkan ke <strong>SEMUA</strong> jadwal di tanggal yang dipilih. Status akan kembali menjadi <strong>Menunggu</strong> untuk review ulang Ketua Panitia.
            </div>
            <form id="editByDateForm" action="" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold text-sm">Jam Mulai</span></label>
                        <input type="time" name="jam_mulai" x-model="editRevisi.jamMulai" class="input input-bordered input-sm" required />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold text-sm">Interval (menit)</span></label>
                        <input type="number" name="interval" x-model="editRevisi.interval" min="5" max="120" class="input input-bordered input-sm" required />
                    </div>
                    <div class="form-control md:col-span-2">
                        <label class="label"><span class="label-text font-semibold text-sm">Link Zoom</span></label>
                        <input type="text" name="link_zoom" x-model="editRevisi.linkZoom" placeholder="https://zoom.us/j/..." class="input input-bordered input-sm" />
                    </div>
                </div>
                <div class="border-t border-slate-100 pt-3">
                    <p class="mb-2 text-sm font-semibold text-black">Penguji</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="form-control">
                            <label class="label"><span class="label-text text-sm">Bacaan Al-Quran</span></label>
                            <select name="penguji_bacaan_al_quran" x-model="editRevisi.penguji.penguji_bacaan_al_quran" class="select select-bordered select-sm">
                                <option value="">-- Pilih Panitia --</option>
                                @foreach($panitias as $p)
                                    <option value="{{ $p->id_panitia }}">{{ $p->nama_lengkap }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text text-sm">Tajwid/Tahsin</span></label>
                            <select name="penguji_tajwid_tahsin" x-model="editRevisi.penguji.penguji_tajwid_tahsin" class="select select-bordered select-sm">
                                <option value="">-- Pilih Panitia --</option>
                                @foreach($panitias as $p)
                                    <option value="{{ $p->id_panitia }}">{{ $p->nama_lengkap }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text text-sm">Hafalan</span></label>
                            <select name="penguji_hafalan" x-model="editRevisi.penguji.penguji_hafalan" class="select select-bordered select-sm">
                                <option value="">-- Pilih Panitia --</option>
                                @foreach($panitias as $p)
                                    <option value="{{ $p->id_panitia }}">{{ $p->nama_lengkap }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text text-sm">Wawancara</span></label>
                            <select name="penguji_wawancara" x-model="editRevisi.penguji.penguji_wawancara" class="select select-bordered select-sm">
                                <option value="">-- Pilih Panitia --</option>
                                @foreach($panitias as $p)
                                    <option value="{{ $p->id_panitia }}">{{ $p->nama_lengkap }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <div class="flex items-center gap-2 w-full justify-between">
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('editByDateModal').close()">Batal</button>
                <button type="submit" form="editByDateForm" class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-rose-700 active:scale-95 shadow-sm">
                    <x-heroicon-s-check class="h-4 w-4" />
                    Simpan Perubahan
                </button>
            </div>
        </x-slot>
    </x-ui.modal>

    {{-- MODAL PERINGATAN MAHASISWA BELUM TERVERIFIKASI --}}
    <x-ui.modal id="unscheduledModal" size="lg">
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-100">
                    <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Peringatan: Ada Mahasantri Belum Terverifikasi</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Mahasantri berikut belum terverifikasi dan tidak masuk dalam jadwal ini</p>
                </div>
            </div>
        </x-slot>
        <x-slot name="body">
            <div class="max-h-[60vh] overflow-y-auto -mr-2 pr-2">
                @php
                    $unscheduled = session('unscheduledMahasantri', []);
                @endphp
                @if(count($unscheduled) > 0)
                    <div class="space-y-2">
                        @foreach($unscheduled as $mhs)
                            <div class="flex items-center justify-between p-3 bg-amber-50 rounded-lg border border-amber-200">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-black">{{ $mhs['id_mahasantri'] }}</span>
                                        <span class="text-[12px] text-black/60">{{ $mhs['nama_lengkap'] }}</span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Belum Terverifikasi</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-sm text-slate-400 py-4">Tidak ada mahasantri yang belum terverifikasi</p>
                @endif
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('unscheduledModal').close()">Tutup</button>
        </x-slot>
    </x-ui.modal>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('unscheduledMahasantri') && count(session('unscheduledMahasantri')) > 0)
                document.getElementById('unscheduledModal').showModal();
            @endif
        });

        function openEditModal({ id, jam, link_zoom }) {
            document.getElementById('editModal-form').action = '{{ route('seleksi.update', ':id') }}'.replace(':id', id);
            document.querySelector('#editModal-form [name="jam"]').value = jam;
            document.querySelector('#editModal-form [name="link_zoom"]').value = link_zoom;
            document.getElementById('editModal').showModal();
        }

        function openCancelModal(id, nama) {
            document.getElementById('cancelModal-form').action = '{{ route('seleksi.destroy', ':id') }}'.replace(':id', id);
            document.getElementById('cancelModal').showModal();
        }

        function openApproveModal(id) {
            document.getElementById('approveModal-form').action = '{{ route('seleksi.approve', ':id') }}'.replace(':id', id);
            document.getElementById('approveModal').showModal();
        }

        function openRejectModal(id) {
            document.getElementById('rejectModal-form').action = '{{ route('seleksi.reject', ':id') }}'.replace(':id', id);
            document.getElementById('rejectModal').showModal();
        }

        const gelombangs = @json($gelombangs->map(function($g) {
            return ['nama' => $g->nama, 'start_date' => $g->start_date, 'end_date' => $g->end_date];
        })->toArray());

        function validateGelombangDate(form) {
            const date = form.querySelector('[name="tanggal"]')?.value;
            if (!date) return true;
            const valid = gelombangs.some(g => date >= g.start_date && date <= g.end_date);
            if (!valid) {
                document.getElementById('form-error').classList.remove('hidden');
                document.getElementById('form-error-message').innerHTML = 'Tanggal tidak masuk dalam rentang gelombang manapun.';
            }
            return valid;
        }

        function validateEditGelombangDate(form) {
            const date = form.querySelector('[name="tanggal"]')?.value;
            if (!date) return true;
            const valid = gelombangs.some(g => date >= g.start_date && date <= g.end_date);
            if (!valid) {
                document.getElementById('edit-form-error').classList.remove('hidden');
                document.getElementById('edit-form-error-message').innerHTML = 'Tanggal tidak masuk dalam rentang gelombang manapun.';
                return false;
            }
            return valid;
        }
    </script>
</div>
@endsection
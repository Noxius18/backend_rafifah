@extends('layouts.app')

@section('content')

@php
    $aspekList = ['Tajwid', 'Tahsin', 'Kelancaran Membaca Al-Qur\'an', 'Wawancara dan Sikap'];
    $aspekMapping = [
        'Tajwid'                            => 'tajwid',
        'Tahsin'                            => 'tahsin',
        "Kelancaran Membaca Al-Qur'an"      => 'kelancaran',
        'Wawancara dan Sikap'               => 'wawancara',
    ];
    $gelombangList = [1 => 'Gelombang 1', 2 => 'Gelombang 2'];

    $isPengawas = auth()->user()->jabatan === 'Pengawas';
    $isPanitia = auth()->user()->jabatan === 'Panitia';

    // Kolom dibalikin komplit
    $columns = [
        ['label' => 'ID',               'field' => 'id_jadwal',    'html' => 'id_html'],
        ['label' => 'Mahasantri',       'field' => 'mhs_nama',     'html' => 'mhs_html'],
        ['label' => 'Gelombang',        'field' => 'gelombang',    'html' => 'gelombang_html'],
        ['label' => 'Tanggal',          'field' => 'tanggal',      'html' => 'tgl_html'],
        ['label' => 'Jam',              'field' => 'jam',          'html' => 'jam_html'],
        ['label' => 'Penanggung Jawab', 'field' => 'pj',           'html' => 'pj_html',   'class' => 'hidden md:table-cell'],
        ['label' => 'Link Zoom',        'field' => 'link_zoom',    'html' => 'link_html', 'class' => 'hidden lg:table-cell'],
        ['label' => 'Aksi',             'field' => 'id_jadwal',    'html' => 'aksi_html', 'class' => 'text-right'],
    ];

    $rows = $jadwals->map(function($j) use ($isPengawas, $isPanitia) {
        $hasil = \App\Models\HasilTes::where('id_jadwal', $j->id_jadwal)->first();
        $statusHasil = $hasil ? $hasil->status : 'Belum Tes';
        
        $aksiHtml = "<div class='flex items-center justify-end gap-0.5'>";
        
        // 1. TOMBOL NILAI (MATA untuk Pengawas, NOTES/KERTAS untuk Panitia)
        if ($isPengawas) {
            $aksiHtml .= "<button type='button' onclick='window.dispatchEvent(new CustomEvent(\"open-nilai-modal\", { detail: { id_jadwal: \"{$j->id_jadwal}\", id_mahasantri: \"{$j->mahasantri?->id_mahasantri}\", nama: \"" . addslashes($j->mahasantri?->nama_lengkap) . "\", status: \"{$statusHasil}\", hasil: " . ($hasil ? json_encode($hasil) : 'null') . "} }))' class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-emerald-600 hover:bg-emerald-50' title='Lihat Nilai'>
                            <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z'/><path stroke-linecap='round' stroke-linejoin='round' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'/></svg>
                          </button>";
        } else {
            // Ini logo Notes/Document yang baru
            $aksiHtml .= "<button type='button' onclick='window.dispatchEvent(new CustomEvent(\"open-nilai-modal\", { detail: { id_jadwal: \"{$j->id_jadwal}\", id_mahasantri: \"{$j->mahasantri?->id_mahasantri}\", nama: \"" . addslashes($j->mahasantri?->nama_lengkap) . "\", status: \"{$statusHasil}\", hasil: " . ($hasil ? json_encode($hasil) : 'null') . "} }))' class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-emerald-600 hover:bg-emerald-50' title='Input Nilai'>
                            <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' /></svg>
                          </button>";
        }

        // 2. TOMBOL REVIEW (Hanya Pengawas & Status Pertimbangan)
        if ($isPengawas && $statusHasil === 'Pertimbangan' && $hasil) {
            $aksiHtml .= "<button type='button' @click=\"openReviewModal('{$hasil->id_hasil}', 'Lulus')\" class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-emerald-600 hover:bg-emerald-50' title='Setujui'><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'/></svg></button>
                          <button type='button' @click=\"openReviewModal('{$hasil->id_hasil}', 'Tidak Lulus')\" class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-rose-600 hover:bg-rose-50' title='Tolak'><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z'/></svg></button>";
        }

        // 3. TOMBOL EDIT/HAPUS (Hanya Panitia)
        if ($isPanitia) {
            $aksiHtml .= "<button type='button' onclick=\"openEditModal({ id: '{$j->id_jadwal}', tanggal: '{$j->tanggal}', jam: '" . ($j->jam ? \Carbon\Carbon::parse($j->jam)->format('H:i') : '') . "', link_zoom: '" . e($j->link_zoom ?? '') . "', penguji_tajwid: '" . e($j->penguji_tajwid ?? '') . "', penguji_tahsin: '" . e($j->penguji_tahsin ?? '') . "', penguji_kelancaran: '" . e($j->penguji_kelancaran ?? '') . "', penguji_wawancara: '" . e($j->penguji_wawancara ?? '') . "' })\" class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-indigo-600 hover:bg-indigo-50' title='Edit'><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10'/></svg></button>
                          <button type='button' onclick=\"openConfirmModal('/seleksi/{$j->id_jadwal}', '" . e($j->mahasantri?->nama_lengkap ?? $j->id_jadwal) . "')\" class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-rose-600 hover:bg-rose-50' title='Hapus'><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0'/></svg></button>";
        }
        $aksiHtml .= "</div>";

        return [
            'id_jadwal'   => $j->id_jadwal,
            'gelombang'   => $j->mahasantri ? \App\Models\User::extractGelombangNama($j->mahasantri->id_mahasantri) : '-',
            'search'      => strtolower("{$j->id_jadwal} {$j->mahasantri?->nama_lengkap} {$j->tanggal}"),
            'id_html'     => "<code class='rounded bg-black/[0.05] px-1.5 py-0.5 text-xs text-black'>{$j->id_jadwal}</code>",
            'mhs_html'    => $j->mahasantri ? "<span class='font-medium text-black'>" . e($j->mahasantri->nama_lengkap) . "</span><br><span class='text-[10px] text-black/60'>" . e($j->mahasantri->id_mahasantri) . "</span>" : "<span class='text-black/50 text-xs'>-</span>",
            'gelombang_html' => $j->mahasantri ? "<span class='rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-purple-200'>" . e(\App\Models\User::extractGelombangNama($j->mahasantri->id_mahasantri)) . "</span>" : "<span class='text-slate-400'>-</span>",
            'tgl_html'    => "<span class='text-xs text-black'>" . \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') . "</span>",
            'jam_html'    => $j->jam ? "<span class='rounded-md bg-slate-50 px-2 py-0.5 text-xs font-mono font-medium text-slate-600 ring-1 ring-slate-200'>" . \Carbon\Carbon::parse($j->jam)->format('H:i') . "</span>" : "<span class='text-slate-400 text-xs'>-</span>",
            
            // Kolom yg dibalikin
            'pj_html'     => $j->penanggungJawab ? "<span class='inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200'><svg xmlns='http://www.w3.org/2000/svg' class='h-3.5 w-3.5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'/></svg>" . e($j->penanggungJawab->nama_lengkap) . "</span>" : "<span class='text-black/50 text-xs'>-</span>",
            'link_html'   => $j->link_zoom ? "<a href='" . e($j->link_zoom) . "' target='_blank' class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-blue-600 hover:bg-blue-50' title='Buka Zoom'><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9A2.25 2.25 0 0013.5 5.25h-9A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75z'/></svg></a>" : "<span class='text-black/50 text-xs'>-</span>",
            
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

    {{-- Data untuk Logic Modal Input Nilai --}}
    selectedMhs: null, selectedJadwal: null, selectedMhsNama: '', statusHasil: '',
    formData: { nilai_tajwid: '', nilai_tahsin: '', nilai_kelancaran: '', nilai_wawancara: '', catatan_penguji: '' },
    saving: false,
    async submitNilai() {
        this.saving = true;
        try {
            const res = await fetch('{{ route('hasil-tes.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ id_mahasantri: this.selectedMhs, id_jadwal: this.selectedJadwal, ...this.formData }),
            });
            const data = await res.json();
            if (res.ok) { this.showToast(data.message, 'success'); setTimeout(() => location.reload(), 1000); }
            else { this.showToast(data.message || 'Gagal menyimpan', 'error'); }
        } catch(e) { this.showToast('Gagal menyimpan nilai', 'error'); } finally { this.saving = false; }
    },

    {{-- Logic untuk Review Pengawas --}}
    reviewId: null, reviewAction: null,
    openReviewModal(hasilId, action) {
        this.reviewId = hasilId; this.reviewAction = action;
        document.getElementById('reviewModal').showModal();
    },
    async confirmReview() {
        try {
            const res = await fetch('/hasil-tes/' + this.reviewId + '/review', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ status: this.reviewAction }),
            });
            const data = await res.json();
            if (res.ok) { this.showToast(data.message, 'success'); setTimeout(() => location.reload(), 1000); }
            else { this.showToast(data.message || 'Gagal', 'error'); }
        } catch(e) { this.showToast('Gagal memperbarui status', 'error'); }
    }
}"
@open-nilai-modal.window="
    selectedJadwal = $event.detail.id_jadwal; selectedMhs = $event.detail.id_mahasantri; selectedMhsNama = $event.detail.nama; statusHasil = $event.detail.status;
    let hasil = $event.detail.hasil;
    if (hasil) {
        formData.nilai_tajwid = hasil.nilai_tajwid || ''; formData.nilai_tahsin = hasil.nilai_tahsin || ''; formData.nilai_kelancaran = hasil.nilai_kelancaran || ''; formData.nilai_wawancara = hasil.nilai_wawancara || ''; formData.catatan_penguji = hasil.catatan_penguji || '';
    } else {
        formData.nilai_tajwid = ''; formData.nilai_tahsin = ''; formData.nilai_kelancaran = ''; formData.nilai_wawancara = ''; formData.catatan_penguji = '';
    }
    document.getElementById('nilaiModal').showModal();
"
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
                @if(auth()->user()->jabatan === 'Panitia')
                <div class="flex items-center gap-2">
                    <button type="button" onclick="document.getElementById('addModal').showModal()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95">
                        <x-heroicon-s-plus class="h-4 w-4" /> Buat Jadwal
                    </button>
                </div>
                @endif
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
                        <x-ui.form-input
                            name="tanggal"
                            label="Tanggal Seleksi"
                            type="date"
                            required
                        />
                    @else
                        <x-ui.form-input name="tanggal" label="Tanggal Seleksi" type="date" required />
                        <p class="text-xs text-red-600 mt-1">
                            <span class="font-medium">Peringatan:</span> Tidak ada gelombang aktif saat ini. Hubungi admin untuk konfigurasi gelombang.
                        </p>
                    @endif
                    <x-ui.form-input name="jam_mulai" label="Jam Mulai" type="time" required />
                    <x-ui.form-input name="interval" label="Interval (menit)" type="number" value="30" min="5" max="120" required />
                    <x-ui.form-input name="link_zoom" label="Link Zoom" placeholder="https://zoom.us/j/..." />
                </div>
                <div class="border-t border-black/10 pt-3">
                    <p class="mb-2 text-sm font-semibold text-black">Tentukan Penguji Materi</p>
                    {{-- FIX: Menggunakan :label="$aspek" agar HTML entity tidak meluber --}}
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

    {{-- MODAL INPUT/LIHAT NILAI --}}
    <x-ui.modal id="nilaiModal" size="lg">
        <x-slot name="header">
            <div class="flex items-center justify-between w-full pr-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100">
                        @if(auth()->user()->jabatan === 'Pengawas')
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">{{ auth()->user()->jabatan === 'Pengawas' ? 'Lihat Nilai' : 'Input Nilai' }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Mahasantri: <span class="font-medium" x-text="selectedMhsNama"></span></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-slate-500 font-medium mb-1">Status Test</p>
                    <span class="rounded-md px-2 py-1 text-xs font-bold ring-1"
                        :class="{ 'bg-emerald-50 text-emerald-700 ring-emerald-200': statusHasil === 'Lulus', 'bg-rose-50 text-rose-700 ring-rose-200': statusHasil === 'Tidak Lulus', 'bg-amber-50 text-amber-700 ring-amber-200': statusHasil === 'Pertimbangan', 'bg-slate-50 text-slate-500 ring-slate-200': statusHasil === 'Belum Tes' }"
                        x-text="statusHasil || 'Belum Tes'">
                    </span>
                </div>
            </div>
        </x-slot>

        <x-slot name="body">
            <div class="max-h-[65vh] overflow-y-auto -mr-2 pr-2 space-y-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs text-slate-500 border-b border-slate-200"><th class="pb-2 text-left font-medium">No</th><th class="pb-2 text-left font-medium">Aspek Penilaian</th><th class="pb-2 text-center font-medium">Nilai</th><th class="pb-2 text-left font-medium">Keterangan</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="py-2 text-slate-500">1</td><td class="py-2 font-medium text-slate-700">Tajwid</td>
                                <td class="py-2 text-center"><input type="number" min="0" max="100" x-model="formData.nilai_tajwid" class="w-20 rounded border border-slate-200 px-2 py-1 text-center text-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none disabled:bg-transparent disabled:border-transparent disabled:font-bold disabled:text-slate-700" {{ auth()->user()->jabatan !== 'Panitia' ? 'disabled' : '' }}></td>
                                <td class="py-2 text-xs text-slate-400">Nilai ilmu tajwid</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-slate-500">2</td><td class="py-2 font-medium text-slate-700">Tahsin</td>
                                <td class="py-2 text-center"><input type="number" min="0" max="100" x-model="formData.nilai_tahsin" class="w-20 rounded border border-slate-200 px-2 py-1 text-center text-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none disabled:bg-transparent disabled:border-transparent disabled:font-bold disabled:text-slate-700" {{ auth()->user()->jabatan !== 'Panitia' ? 'disabled' : '' }}></td>
                                <td class="py-2 text-xs text-slate-400">Nilai tahsin bacaan</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-slate-500">3</td><td class="py-2 font-medium text-slate-700">Kelancaran Bacaan</td>
                                <td class="py-2 text-center"><input type="number" min="0" max="100" x-model="formData.nilai_kelancaran" class="w-20 rounded border border-slate-200 px-2 py-1 text-center text-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none disabled:bg-transparent disabled:border-transparent disabled:font-bold disabled:text-slate-700" {{ auth()->user()->jabatan !== 'Panitia' ? 'disabled' : '' }}></td>
                                <td class="py-2 text-xs text-slate-400">Nilai kelancaran membaca</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-slate-500">4</td><td class="py-2 font-medium text-slate-700">Wawancara</td>
                                <td class="py-2 text-center"><input type="number" min="0" max="100" x-model="formData.nilai_wawancara" class="w-20 rounded border border-slate-200 px-2 py-1 text-center text-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none disabled:bg-transparent disabled:border-transparent disabled:font-bold disabled:text-slate-700" {{ auth()->user()->jabatan !== 'Panitia' ? 'disabled' : '' }}></td>
                                <td class="py-2 text-xs text-slate-400">Nilai hasil wawancara</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-slate-700">Rata-rata:</span>
                        <span class="text-lg font-bold text-emerald-600" x-text="(() => { const vals = [formData.nilai_tajwid, formData.nilai_tahsin, formData.nilai_kelancaran, formData.nilai_wawancara].map(v => parseInt(v)).filter(v => !isNaN(v)); return vals.length ? Math.round(vals.reduce((a,b) => a+b, 0) / vals.length) : '-'; })()"></span>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold text-sm text-slate-700">Catatan Penguji</span></label>
                    <textarea x-model="formData.catatan_penguji" rows="3" class="textarea textarea-bordered text-sm w-full focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 disabled:bg-slate-50 disabled:text-slate-700" placeholder="Catatan untuk mahasantri ini..." {{ auth()->user()->jabatan !== 'Panitia' ? 'disabled' : '' }}></textarea>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('nilaiModal').close()">Tutup</button>
            @if(auth()->user()->jabatan === 'Panitia')
            <button type="button" @click="submitNilai()" :disabled="saving" class="btn btn-success btn-sm gap-1.5">
                <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Nilai'"></span>
            </button>
            @endif
        </x-slot>
    </x-ui.modal>

    {{-- MODAL REVIEW PENGAWAS --}}
    <x-ui.modal id="reviewModal" size="sm">
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-100">
                    <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Review Pertimbangan</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Konfirmasi keputusan untuk mahasantri ini</p>
                </div>
            </div>
        </x-slot>
        <x-slot name="body">
            <div class="space-y-4 py-2">
                <div class="rounded-lg border border-amber-100 bg-amber-50 p-4 text-sm text-amber-800">
                    <p>Ubah status mahasantri ini menjadi:</p>
                    <p class="mt-2 text-center text-lg font-bold" x-text="reviewAction === 'Lulus' ? '✅ LULUS' : '❌ TIDAK LULUS'"></p>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('reviewModal').close()">Batal</button>
            <button type="button" @click="confirmReview()" class="btn btn-sm gap-1.5 text-white border-none" :class="reviewAction === 'Lulus' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'">
                <span x-text="reviewAction === 'Lulus' ? 'Setujui' : 'Tolak'"></span>
            </button>
        </x-slot>
    </x-ui.modal>

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
                <x-ui.form-input name="tanggal" label="Tanggal Seleksi" type="date" required />
                <x-ui.form-input name="jam" label="Jam" type="time" />
                <x-ui.form-input name="link_zoom" label="Link Zoom" placeholder="https://zoom.us/j/..." />
                <div class="border-t border-slate-100 pt-3">
                    <p class="mb-2 text-sm font-semibold text-slate-700">Tentukan Penguji per Aspek</p>
                    {{-- FIX: Menggunakan :label="$aspek" agar HTML entity tidak meluber --}}
                    <div class="grid grid-cols-1 gap-4">
                        @foreach ($aspekMapping as $aspek => $field)
                            <x-ui.form-select name="penguji_{{ $field }}" :label="$aspek" :options="$panitias->pluck('nama_lengkap', 'id_panitia')->toArray()" placeholder="-- Pilih Panitia --" />
                        @endforeach
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm text-black hover:bg-black/[0.05]" onclick="document.getElementById('editModal').close()">Batal</button>
            <button type="submit" form="editModal-form" class="btn btn-success btn-sm">Perbarui</button>
        </x-slot>
    </x-ui.modal-form>

    <x-ui.modal-confirm id="deleteModal" title="Konfirmasi Hapus" body-text="Hapus jadwal seleksi untuk" confirm-label="Hapus" />

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
        // Tampilkan modal otomatis jika ada mahasantri yang belum terverifikasi
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('unscheduledMahasantri') && count(session('unscheduledMahasantri')) > 0)
                document.getElementById('unscheduledModal').showModal();
            @endif
        });
    </script>
</div>

<script>
    function openEditModal({ id, tanggal, jam, link_zoom, penguji_tajwid, penguji_tahsin, penguji_kelancaran, penguji_wawancara }) {
        document.getElementById('editModal-form').action = `/seleksi/${id}`;
        document.querySelector('#editModal-form [name="tanggal"]').value = tanggal;
        document.querySelector('#editModal-form [name="jam"]').value = jam;
        document.querySelector('#editModal-form [name="link_zoom"]').value = link_zoom;
        document.querySelector('#editModal-form [name="penguji_tajwid"]').value = penguji_tajwid;
        document.querySelector('#editModal-form [name="penguji_tahsin"]').value = penguji_tahsin;
        document.querySelector('#editModal-form [name="penguji_kelancaran"]').value = penguji_kelancaran;
        document.querySelector('#editModal-form [name="penguji_wawancara"]').value = penguji_wawancara;
        document.getElementById('editModal').showModal();
    }

    function openConfirmModal(url, name) {
        document.getElementById('deleteModal-name').textContent = name;
        let form = document.getElementById('deleteModal-form');
        form.action = url;
        document.getElementById('deleteModal').showModal();
    }

    // Validasi tanggal sebelum submit form
    function validateGelombangDate(form) {
        const tanggalInput = form.querySelector('[name="tanggal"]');
        const errorDiv = document.getElementById('form-error');
        const errorMessage = document.getElementById('form-error-message');

        if (!tanggalInput || !errorDiv) return true;

        const selectedDate = tanggalInput.value;

        // Data gelombang dari server (JSON encoded)
        const gelombangs = @json($gelombangs->map(function($g) {
            return [
                'nama' => $g->nama,
                'start_date' => $g->start_date,
                'end_date' => $g->end_date
            ];
        })->toArray());

        // Cek apakah tanggal ada dalam salah satu rentang gelombang
        let isValid = false;
        let matchedGelombang = null;

        for (let gelombang of gelombangs) {
            if (selectedDate >= gelombang.start_date && selectedDate <= gelombang.end_date) {
                isValid = true;
                matchedGelombang = gelombang.nama;
                break;
            }
        }

        if (!isValid) {
            errorDiv.classList.remove('hidden');
            errorMessage.innerHTML = 'Tanggal tidak masuk dalam rentang gelombang manapun. Silakan pilih tanggal yang valid.';
            return false;
        }

        errorDiv.classList.add('hidden');
        return true;
    }

    // Validasi tanggal untuk edit modal
    function validateEditGelombangDate(form) {
        const tanggalInput = form.querySelector('[name="tanggal"]');
        const errorDiv = document.getElementById('edit-form-error');
        const errorMessage = document.getElementById('edit-form-error-message');

        if (!tanggalInput || !errorDiv) return true;

        const selectedDate = tanggalInput.value;

        // Data gelombang dari server
        const gelombangs = @json($gelombangs->map(function($g) {
            return [
                'nama' => $g->nama,
                'start_date' => $g->start_date,
                'end_date' => $g->end_date
            ];
        })->toArray());

        // Cek apakah tanggal ada dalam salah satu rentang gelombang
        let isValid = false;

        for (let gelombang of gelombangs) {
            if (selectedDate >= gelombang.start_date && selectedDate <= gelombang.end_date) {
                isValid = true;
                break;
            }
        }

        if (!isValid) {
            errorDiv.classList.remove('hidden');
            errorMessage.innerHTML = 'Tanggal tidak masuk dalam rentang gelombang manapun. Silakan pilih tanggal yang valid.';
            return false;
        }

        errorDiv.classList.add('hidden');
        return true;
    }
</script>

@endsection
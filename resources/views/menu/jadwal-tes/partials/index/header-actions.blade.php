<div class="flex items-center justify-between">
    <div class="flex items-start gap-3">
        <div class="mt-1 h-7 w-1 rounded-full bg-emerald-500"></div>
        <div>
            <h1 class="text-xl font-semibold text-black">Seleksi & Penilaian</h1>
            <p class="text-sm text-black">Kelola jadwal seleksi dan penilaian mahasantri.</p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        @php
            $isKetuaPanitia = auth()->user()->jabatan === 'Ketua Panitia';
            $isPanitia = auth()->user()->jabatan === 'Panitia';
        @endphp

        @if(($isPanitia || $isKetuaPanitia) && $activeGelombang)
            <a href="{{ route('laporan.panitia.seleksi', $activeGelombang->id_gelombang ?? $activeGelombang->id) }}" target="_blank"
                class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100 active:scale-95 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak Laporan Panitia
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

            <button type="button" x-on:click="document.getElementById('sendBulkModal').showModal()"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 active:scale-95 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                Kirim Hasil Penilaian
            </button>

            <button type="button" x-on:click="document.getElementById('addModal').showModal()"
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

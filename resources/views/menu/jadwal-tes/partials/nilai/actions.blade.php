<div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3">
    <div class="text-xs text-slate-400">
        @if($statusHasil === 'Lulus' || $statusHasil === 'Tidak Lulus')
            Hasil sudah final.
        @elseif($isPertimbangan)
            @if($isKetuaPanitia)
                Periksa nilai, lalu beri keputusan final.
            @else
                Menunggu review Ketua Panitia.
            @endif
        @elseif($isPanitia)
            Isi semua aspek, lalu simpan hasil.
        @endif
    </div>

    <div class="flex items-center gap-2">
        @if(($isCreator || $isPanitia) && !$isPertimbangan && $isApproved)
            <button type="button" x-on:click="simpanHasil()" :disabled="saving"
                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95">
                <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Simpan Hasil
            </button>
        @endif
        @if($isKetuaPanitia && $isPertimbangan)
            <button type="button" x-on:click="confirmReview('Lulus')" :disabled="saving"
                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95">
                <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                ✅ Setujui (Lulus)
            </button>
            <button type="button" x-on:click="confirmReview('Tidak Lulus')" :disabled="saving"
                class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-4 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50 active:scale-95">
                <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                ❌ Tolak (Tidak Lulus)
            </button>
        @endif
    </div>
</div>

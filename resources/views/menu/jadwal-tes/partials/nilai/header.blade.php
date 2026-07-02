<div class="flex items-start justify-between">
    <div class="flex items-start gap-3">
        <div class="mt-1 h-7 w-1 rounded-full bg-emerald-500"></div>
        <div>
            <h1 class="text-xl font-semibold text-black">{{ $jadwalTes->mahasantri?->nama_lengkap ?? 'Unknown' }}</h1>
            <div class="mt-0.5 flex items-center gap-2">
                <span class="text-sm text-black/60">{{ $jadwalTes->mahasantri?->id_mahasantri ?? '' }}</span>
                <span class="text-black/20">•</span>
                <span class="text-sm text-black/60">{{ \Carbon\Carbon::parse($jadwalTes->tanggal)->format('d F Y') }}</span>
                @if($jadwalTes->jam)
                    <span class="text-black/20">•</span>
                    <span class="text-sm text-black/60">{{ \Carbon\Carbon::parse($jadwalTes->jam)->format('H:i') }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        @if($statusHasil === 'Lulus')
            <span class="rounded-md bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200">✅ Lulus</span>
        @elseif($statusHasil === 'Tidak Lulus')
            <span class="rounded-md bg-rose-50 px-3 py-1 text-sm font-semibold text-rose-700 ring-1 ring-rose-200">❌ Tidak Lulus</span>
        @elseif($isPertimbangan)
            <span class="rounded-md bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-700 ring-1 ring-amber-200">⚠️ Pertimbangan</span>
        @else
            <span class="rounded-md bg-slate-50 px-3 py-1 text-sm font-semibold text-slate-500 ring-1 ring-slate-200">⏳ Belum Tes</span>
        @endif
        <a href="{{ route('seleksi.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
            <x-heroicon-s-arrow-left class="h-4 w-4" /> Kembali
        </a>
    </div>
</div>

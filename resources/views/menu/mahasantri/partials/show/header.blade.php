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
            <button type="button" x-on:click="document.getElementById('editModal').showModal()"
                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95">
                <x-heroicon-s-pencil-square class="h-4 w-4" />
                Edit
            </button>
            <button type="button" x-on:click="openDeleteModal()"
                class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-rose-700 active:scale-95">
                <x-heroicon-s-trash class="h-4 w-4" />
                Hapus
            </button>
        @endif
    </div>
</div>

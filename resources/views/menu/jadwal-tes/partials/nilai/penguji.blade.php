<div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3">
    <div class="mb-2 flex items-center gap-2">
        <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
        <span class="text-sm font-semibold text-indigo-700">Penguji</span>
    </div>
    <div class="flex flex-wrap gap-2">
        @foreach($nilaiPerAspek as $aspek => $data)
            <div class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-indigo-600 ring-1 ring-indigo-200">
                <span class="text-indigo-400">•</span>
                <span>{{ $aspek }}:</span>
                <span class="font-bold">{{ $data['penguji'] ?? '-' }}</span>
                @if($data['id_panitia'] == $pageState['userId'])
                    <span class="text-[10px] italic text-indigo-400">(Anda)</span>
                @endif
            </div>
        @endforeach
    </div>
    @if($isCreator && !$isKetuaPanitia)
        <p class="mt-2 text-xs text-indigo-500">Anda pembuat jadwal — bisa input semua aspek + simpan hasil final.</p>
    @endif
    @if(!$isCreator && !$isKetuaPanitia && count($pageState['tugas']) > 0)
        <p class="mt-2 text-xs text-indigo-500">Anda hanya bisa input aspek yang ditugaskan.</p>
    @endif
</div>

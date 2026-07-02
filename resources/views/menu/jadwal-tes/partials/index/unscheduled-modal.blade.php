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
                <p class="mt-0.5 text-xs text-slate-500">Mahasantri berikut belum terverifikasi dan tidak masuk dalam jadwal ini</p>
            </div>
        </div>
    </x-slot>
    <x-slot name="body">
        @php($unscheduled = session('unscheduledMahasantri', []))
        <div class="max-h-[60vh] overflow-y-auto -mr-2 pr-2">
            @if(count($unscheduled) > 0)
                <div class="space-y-2">
                    @foreach($unscheduled as $mhs)
                        <div class="flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 p-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-black">{{ $mhs['id_mahasantri'] }}</span>
                                    <span class="text-[12px] text-black/60">{{ $mhs['nama_lengkap'] }}</span>
                                </div>
                            </div>
                            <span class="rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Belum Terverifikasi</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="py-4 text-center text-sm text-slate-400">Tidak ada mahasantri yang belum terverifikasi</p>
            @endif
        </div>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-ghost btn-sm" x-on:click="document.getElementById('unscheduledModal').close()">Tutup</button>
    </x-slot>
</x-ui.modal>

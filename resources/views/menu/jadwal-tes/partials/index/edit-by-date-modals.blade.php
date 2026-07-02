<x-ui.modal id="editByDateSelectModal" size="md">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-100">
                <x-heroicon-s-pencil-square class="h-5 w-5 text-rose-600" />
            </div>
            <div>
                <h3 class="text-lg font-semibold text-slate-800">Pilih Tanggal Revisi</h3>
                <p class="mt-0.5 text-xs text-slate-500">Pilih tanggal jadwal yang akan diperbaiki</p>
            </div>
        </div>
    </x-slot>
    <x-slot name="body">
        <div class="max-h-[60vh] space-y-2 overflow-y-auto">
            @forelse($jadwalsRevisiByTanggal as $group)
                <button type="button" x-on:click="openEditByDateGroup(@js($group)); document.getElementById('editByDateSelectModal').close()"
                    class="w-full rounded-lg border border-slate-200 p-3 text-left transition hover:border-rose-300 hover:bg-rose-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($group['tanggal'])->format('d/m/Y') }}</span>
                            <span class="ml-2 text-xs text-slate-500">{{ $group['total'] }} jadwal</span>
                        </div>
                        <svg class="h-4 w-4 flex-shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </div>
                    @if($group['catatan_perubahan'])
                        <div class="mt-2 rounded bg-rose-50 px-2 py-1.5 text-xs leading-relaxed text-rose-600">📝 {{ $group['catatan_perubahan'] }}</div>
                    @endif
                </button>
            @empty
                <p class="py-4 text-center text-sm text-slate-400">Tidak ada jadwal dengan status Revisi.</p>
            @endforelse
        </div>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-ghost btn-sm" x-on:click="document.getElementById('editByDateSelectModal').close()">Tutup</button>
    </x-slot>
</x-ui.modal>

<x-ui.modal id="editByDateModal" size="xl">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-100">
                <x-heroicon-s-pencil-square class="h-5 w-5 text-rose-600" />
            </div>
            <div>
                <h3 class="text-lg font-semibold text-slate-800">Edit Jadwal Revisi</h3>
                <p class="mt-0.5 text-xs text-slate-500" x-text="'Tanggal: ' + editRevisi.selectedTanggal"></p>
            </div>
        </div>
    </x-slot>
    <x-slot name="body">
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
            Perubahan akan diterapkan ke <strong>SEMUA</strong> jadwal di tanggal yang dipilih. Status akan kembali menjadi <strong>Menunggu</strong> untuk review ulang Ketua Panitia.
        </div>
        <form id="editByDateForm" action="" method="POST" class="space-y-4" x-on:submit="validateEditByDateForm($event)">
            @csrf
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text text-sm font-semibold">Tanggal Ujian / Seleksi</span></label>
                    <input type="date" name="tanggal_baru" x-model="editRevisi.selectedTanggal" class="input input-bordered input-sm w-full" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-sm font-semibold">Jam Mulai</span></label>
                    <input type="time" name="jam_mulai" x-model="editRevisi.jamMulai" class="input input-bordered input-sm" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-sm font-semibold">Interval (menit)</span></label>
                    <input type="number" name="interval" x-model="editRevisi.interval" min="5" max="120" class="input input-bordered input-sm" required />
                </div>
                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text text-sm font-semibold">Link Zoom</span></label>
                    <input type="text" name="link_zoom" x-model="editRevisi.linkZoom" placeholder="https://zoom.us/j/..." class="input input-bordered input-sm" />
                </div>
            </div>
            <div class="border-t border-slate-100 pt-3">
                <p class="mb-2 text-sm font-semibold text-black">Penguji</p>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    @foreach ([
                        'penguji_bacaan_al_quran' => 'Bacaan Al-Quran',
                        'penguji_tajwid_tahsin' => 'Tajwid/Tahsin',
                        'penguji_hafalan' => 'Hafalan',
                        'penguji_wawancara' => 'Wawancara',
                    ] as $field => $label)
                        <div class="form-control">
                            <label class="label"><span class="label-text text-sm">{{ $label }}</span></label>
                            <select name="{{ $field }}" x-model="editRevisi.penguji.{{ $field }}" class="select select-bordered select-sm">
                                <option value="">-- Pilih Panitia --</option>
                                @foreach($panitias as $panitia)
                                    <option value="{{ $panitia->id_panitia }}">{{ $panitia->nama_lengkap }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer">
        <div class="flex w-full items-center justify-between gap-2">
            <button type="button" class="btn btn-ghost btn-sm" x-on:click="document.getElementById('editByDateModal').close()">Batal</button>
            <button type="submit" form="editByDateForm" class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-rose-700 active:scale-95 shadow-sm">
                <x-heroicon-s-check class="h-4 w-4" />
                Simpan Perubahan
            </button>
        </div>
    </x-slot>
</x-ui.modal>

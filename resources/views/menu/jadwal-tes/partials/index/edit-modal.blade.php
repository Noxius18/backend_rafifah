<x-ui.modal-form id="editModal" title="Edit Jadwal Seleksi" size="lg">
    <x-slot name="body">
        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
            <div class="flex items-start gap-2">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="mb-1 text-sm font-medium text-slate-700">Rentang Gelombang</p>
                    <p class="text-xs text-slate-600">
                        @forelse($gelombangs as $gelombang)
                            {{ $gelombang->nama }}: {{ Carbon\Carbon::parse($gelombang->start_date)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($gelombang->end_date)->format('d/m/Y') }}@if(!$loop->last) • @endif
                        @empty
                            Tidak ada gelombang dikonfigurasi
                        @endforelse
                    </p>
                </div>
            </div>
        </div>

        <form id="editModal-form" action="" method="POST" class="space-y-3">
            @csrf
            @method('PUT')
            <x-ui.form-input name="jam" label="Jam Mulai" type="time" required />
            <x-ui.form-input name="link_zoom" label="Link Zoom" placeholder="https://zoom.us/j/..." />
            <div class="border-t border-slate-100 pt-3">
                <p class="text-sm text-slate-500">*Tanggal tidak dapat diubah dari modal edit tunggal.</p>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-ghost btn-sm text-black hover:bg-black/[0.05]" x-on:click="document.getElementById('editModal').close()">Batal</button>
        <button type="submit" form="editModal-form" class="btn btn-success btn-sm">Perbarui</button>
    </x-slot>
</x-ui.modal-form>

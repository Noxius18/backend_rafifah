<x-ui.modal-form id="sendBulkModal" title="Jadwalkan Surat Kelulusan Massal" size="md">
    <x-slot name="body">
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3">
            <div class="flex items-start gap-2">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="mb-1 text-sm font-medium text-blue-800">Informasi Penjadwalan</p>
                    <p class="text-xs text-blue-700">Sistem akan otomatis mendeteksi <strong class="font-bold">Gelombang yang Aktif</strong> saat ini. Email pengumuman hanya akan dikirimkan kepada mahasantri di gelombang tersebut yang nilainya sudah direview (Lulus/Tidak Lulus).</p>
                </div>
            </div>
        </div>
        <form id="sendBulkModal-form" action="{{ route('seleksi.send-bulk-results') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <p class="mb-2 text-sm font-semibold">Tentukan Waktu Pengiriman Otomatis</p>
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.form-input name="tanggal_kirim" label="Tanggal Kirim" type="date" required />
                    <x-ui.form-input name="jam_kirim" label="Jam Kirim" type="time" required />
                </div>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-ghost btn-sm text-black hover:bg-black/[0.05]" x-on:click="document.getElementById('sendBulkModal').close()">Batal</button>
        <button type="submit" form="sendBulkModal-form" class="btn border-none bg-emerald-600 btn-sm text-white hover:bg-emerald-700">Jadwalkan Email</button>
    </x-slot>
</x-ui.modal-form>

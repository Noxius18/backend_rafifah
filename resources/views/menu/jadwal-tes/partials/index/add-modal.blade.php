<x-ui.modal-form id="addModal" title="Buat Jadwal Seleksi Baru" size="xl">
    <x-slot name="body">
        @if($activeGelombang)
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3">
                <div class="flex items-start gap-2">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="mb-1 text-sm font-medium text-blue-800">Gelombang Aktif</p>
                        <p class="text-xs text-blue-700">{{ $activeGelombang->nama }} ({{ Carbon\Carbon::parse($activeGelombang->start_date)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($activeGelombang->end_date)->format('d/m/Y') }})</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="-mr-2 pr-2">
            @if($errors->any() && old('_form') === 'add-jadwal')
                <x-ui.alert type="error" alert="Periksa kembali data jadwal.">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            <div id="form-error" class="mb-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
                <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium" id="form-error-message"></div>
                    </div>
                </div>
            </div>

            <form id="addModal-form" action="{{ route('seleksi.store') }}" method="POST" class="space-y-4" x-on:submit="validateAddForm($event)" novalidate>
                @csrf
                <input type="hidden" name="_form" value="add-jadwal">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <x-ui.form-input name="tanggal" label="Tanggal Seleksi" type="date" required value="{{ old('tanggal') }}" />
                    <x-ui.form-input name="jam_mulai" label="Jam Mulai" type="time" required value="{{ old('jam_mulai') }}" />
                    <x-ui.form-input name="interval" label="Interval (menit)" type="number" value="{{ old('interval', 30) }}" min="5" max="120" required />
                    <x-ui.form-input name="link_zoom" label="Link Zoom" placeholder="https://zoom.us/j/..." value="{{ old('link_zoom') }}" />
                </div>
                <div class="border-t border-black/10 pt-3">
                    <p class="mb-2 text-sm font-semibold text-black">Tentukan Penguji Materi</p>
                    <div class="grid grid-cols-1 gap-4">
                        @foreach ($aspekMapping as $aspek => $field)
                            <x-ui.form-select name="penguji_{{ $field }}" :label="$aspek" :options="$panitias->pluck('nama_lengkap', 'id_panitia')->toArray()" placeholder="-- Pilih Panitia --" :selected="old('penguji_' . $field)" />
                        @endforeach
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-ghost btn-sm text-black hover:bg-black/[0.05]" x-on:click="document.getElementById('addModal').close()">Batal</button>
        <button type="submit" form="addModal-form" class="btn btn-success btn-sm">Simpan</button>
    </x-slot>
</x-ui.modal-form>

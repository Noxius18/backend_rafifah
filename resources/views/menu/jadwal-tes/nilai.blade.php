@extends('layouts.app')

@section('content')

@php
    $userId = auth()->user()->id_panitia;
    $isCreator = $jadwalTes->penanggung_jawab == $userId;

    // Cek aspek yang ditugaskan ke panitia ini
    $tugas = [];
    if ($jadwalTes->penguji_bacaan_al_quran == $userId || $isCreator) $tugas[] = 'bacaan';
    if ($jadwalTes->penguji_tajwid_tahsin == $userId || $isCreator) $tugas[] = 'tajwid';
    if ($jadwalTes->penguji_hafalan == $userId || $isCreator) $tugas[] = 'hafalan';
    if ($jadwalTes->penguji_wawancara == $userId || $isCreator) $tugas[] = 'wawancara';

    $canInput = count($tugas) > 0;
    $isKetuaPanitia = auth()->user()->jabatan === 'Ketua Panitia';
@endphp

<div x-data="{
    toast: { show: false, message: '', type: 'success', timer: null },
    showToast(message, type = 'success') {
        if (this.toast.timer) clearTimeout(this.toast.timer);
        Object.assign(this.toast, { message, type, show: true, timer: setTimeout(() => this.toast.show = false, 4000) });
    },
    selectedMhs: null, selectedMhsNama: '',
    formData: { nilai_bacaan_al_quran: '', nilai_tajwid_tahsin: '', nilai_hafalan: '', nilai_wawancara: '', catatan_penguji: '' },
    saving: false,
    previewResult: null,

    openNilaiModal(mhs, mhsNama, hasil = null) {
        this.selectedMhs = mhs; this.selectedMhsNama = mhsNama; this.previewResult = null;
        if (hasil) {
            this.formData = {
                nilai_bacaan_al_quran: hasil.nilai_bacaan_al_quran || '',
                nilai_tajwid_tahsin: hasil.nilai_tajwid_tahsin || '',
                nilai_hafalan: hasil.nilai_hafalan || '',
                nilai_wawancara: hasil.nilai_wawancara || '',
                catatan_penguji: hasil.catatan_penguji || ''
            };
        } else {
            this.formData = { nilai_bacaan_al_quran: '', nilai_tajwid_tahsin: '', nilai_hafalan: '', nilai_wawancara: '', catatan_penguji: '' };
        }
        nilaiModal.showModal();
    },

    async submitNilai() {
        this.saving = true;
        try {
            const res = await fetch('{{ route('hasil-tes.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ id_mahasantri: this.selectedMhs, id_jadwal: '{{ $jadwalTes->id_jadwal }}', ...this.formData }),
            });
            const data = await res.json();
            if (res.ok) { this.showToast(data.message || 'Nilai tersimpan', 'success'); setTimeout(() => location.reload(), 1200); }
            else { this.showToast(data.message || 'Gagal', 'error'); }
        } catch(e) { this.showToast('Gagal menyimpan', 'error'); }
        finally { this.saving = false; }
    },

    async hitungHasil() {
        try {
            const res = await fetch('{{ route('hasil-tes.preview-hasil') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ id_mahasantri: this.selectedMhs, id_jadwal: '{{ $jadwalTes->id_jadwal }}' }),
            });
            const data = await res.json();
            if (res.ok) {
                this.previewResult = { total_nilai: data.total_nilai, rata_rata: data.rata_rata, status: data.status };
                this.showToast(data.message, 'success');
            } else {
                this.showToast(data.error || 'Gagal menghitung', 'error');
            }
        } catch(e) { this.showToast('Gagal menghitung', 'error'); }
    },

    async simpanHasil() {
        this.saving = true;
        try {
            const res = await fetch('{{ route('hasil-tes.simpan-hasil') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ id_mahasantri: this.selectedMhs, id_jadwal: '{{ $jadwalTes->id_jadwal }}' }),
            });
            const data = await res.json();
            if (res.ok) { this.showToast(data.message, 'success'); setTimeout(() => location.reload(), 1200); }
            else { this.showToast(data.error || 'Gagal', 'error'); }
        } catch(e) { this.showToast('Gagal menyimpan', 'error'); }
        finally { this.saving = false; }
    },

    reviewPertimbanganId: null, reviewPertimbanganAction: null,
    openReviewModal(hasilId, action) {
        this.reviewPertimbanganId = hasilId; this.reviewPertimbanganAction = action; reviewModal.showModal();
    },
    async confirmReview() {
        try {
            const res = await fetch('/hasil-tes/' + this.reviewPertimbanganId + '/review', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ status: this.reviewPertimbanganAction }),
            });
            const data = await res.json();
            if (res.ok) { this.showToast(data.message, 'success'); setTimeout(() => location.reload(), 1000); }
            else { this.showToast(data.message || 'Gagal', 'error'); }
        } catch(e) { this.showToast('Gagal', 'error'); }
    }
}"
x-init="@if(session('success')) showToast('{{ session('success') }}') @endif @if(session('error')) showToast('{{ session('error') }}', 'error') @endif">

    <x-ui.toast />
    <x-ui.sidebar>
        <section class="space-y-6 px-1 py-2">
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-1 h-7 w-1 rounded-full bg-emerald-500"></div>
                    <div>
                        <h1 class="text-xl font-semibold text-slate-800">Nilai — {{ $jadwalTes->mahasantri?->nama_lengkap ?? 'Unknown' }}</h1>
                        <p class="text-sm text-slate-400">{{ $jadwalTes->mahasantri?->id_mahasantri ?? '' }} &bull; {{ \Carbon\Carbon::parse($jadwalTes->tanggal)->format('d F Y') }} @if($jadwalTes->jam) &bull; {{ \Carbon\Carbon::parse($jadwalTes->jam)->format('H:i') }} @endif</p>
                    </div>
                </div>
                <a href="{{ route('seleksi.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    <x-heroicon-s-arrow-left class="h-4 w-4" /> Kembali
                </a>
            </div>

            {{-- Info Aspek yang Ditugaskan --}}
            @if(count($tugas) > 0 && !$isCreator)
            <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-3 text-sm text-emerald-700">
                <p class="font-medium">Aspek yang Anda tugaskan:</p>
                <div class="mt-1 flex flex-wrap gap-2">
                    @foreach($tugas as $t)
                        <span class="inline-block rounded-md bg-white px-2 py-0.5 text-xs font-medium text-emerald-600 ring-1 ring-emerald-200">{{ ucfirst($t) }}</span>
                    @endforeach
                </div>
                @if(!$isCreator && !$isKetuaPanitia)
                    <p class="text-xs text-emerald-500 mt-1">*Anda hanya bisa menginput nilai untuk aspek yang ditugaskan.</p>
                @endif
            </div>
            @elseif($isCreator)
            <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-700">
                <p class="font-medium">Anda adalah pembuat jadwal. Anda bisa input semua aspek + hitung & simpan hasil.</p>
            </div>
            @endif

            {{-- Penguji Info --}}
            <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-sm text-indigo-700">
                <p class="font-medium">Penguji:</p>
                <div class="mt-1 space-y-1 text-xs">
                    @if($jadwalTes->pengujiBacaanAlquran) <div><strong>Bacaan Al-Qur'an:</strong> {{ $jadwalTes->pengujiBacaanAlquran->nama_lengkap }} @if($jadwalTes->pengujiBacaanAlquran->id_panitia == $userId) <span class="text-indigo-400">(Anda)</span> @endif</div> @endif
                    @if($jadwalTes->pengujiTajwidTahsin) <div><strong>Tajwid:</strong> {{ $jadwalTes->pengujiTajwidTahsin->nama_lengkap }} @if($jadwalTes->pengujiTajwidTahsin->id_panitia == $userId) <span class="text-indigo-400">(Anda)</span> @endif</div> @endif
                    @if($jadwalTes->pengujiHafalan) <div><strong>Hafalan:</strong> {{ $jadwalTes->pengujiHafalan->nama_lengkap }} @if($jadwalTes->pengujiHafalan->id_panitia == $userId) <span class="text-indigo-400">(Anda)</span> @endif</div> @endif
                    @if($jadwalTes->pengujiWawancara) <div><strong>Wawancara:</strong> {{ $jadwalTes->pengujiWawancara->nama_lengkap }} @if($jadwalTes->pengujiWawancara->id_panitia == $userId) <span class="text-indigo-400">(Anda)</span> @endif</div> @endif
                </div>
            </div>

            {{-- Tabel Mahasantri --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-100 text-left">
                            <tr class="text-xs font-medium text-slate-400">
                                <th class="px-4 py-3">No</th>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Total</th>
                                <th class="px-4 py-3">Rata2</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($mahasantris as $index => $m)
                                @php $hasil = $hasilTes->get($m->id_mahasantri); @endphp
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-slate-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-700">{{ $m->nama_lengkap }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($hasil && $hasil->total_nilai !== null)
                                            <span class="text-sm font-semibold">{{ $hasil->total_nilai }}</span>
                                        @else
                                            <span class="text-xs text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($hasil && $hasil->total_nilai !== null)
                                            <span class="text-sm">{{ $hasil->rata_rata ?? $hasil->total_nilai }}</span>
                                        @else
                                            <span class="text-xs text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($hasil && $hasil->status !== 'Belum Tes')
                                            @if ($hasil->status === 'Lulus') <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Lulus</span>
                                            @elseif ($hasil->status === 'Tidak Lulus') <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200">Tidak Lulus</span>
                                            @elseif ($hasil->status === 'Pertimbangan') <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">Pertimbangan</span>
                                            @endif
                                        @else
                                            <span class="rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-400 ring-1 ring-slate-200">Belum Dinilai</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-0.5">
                                            <button type="button" @click="openNilaiModal('{{ $m->id_mahasantri }}', '{{ $m->nama_lengkap }}', @js($hasil))"
                                                class="inline-flex items-center justify-center rounded-md p-2 text-emerald-600 transition hover:bg-emerald-50"
                                                title="{{ $canInput ? 'Input/Lihat Nilai' : 'Lihat Nilai' }}">
                                                @if($canInput)
                                                    <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'/></svg>
                                                @else
                                                    <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z'/><path stroke-linecap='round' stroke-linejoin='round' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'/></svg>
                                                @endif
                                            </button>
                                            @if($isKetuaPanitia && $hasil && $hasil->status === 'Pertimbangan')
                                                <button type="button" @click="openReviewModal('{{ $hasil->id_hasil }}', 'Lulus')" class="inline-flex items-center justify-center rounded-md p-2 text-emerald-600 hover:bg-emerald-50" title="Setujui"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'/></svg></button>
                                                <button type="button" @click="openReviewModal('{{ $hasil->id_hasil }}', 'Tidak Lulus')" class="inline-flex items-center justify-center rounded-md p-2 text-rose-600 hover:bg-rose-50" title="Tolak"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z'/></svg></button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">Belum ada mahasantri.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </x-ui.sidebar>

    {{-- Modal: Input/Lihat Nilai --}}
    <x-ui.modal id="nilaiModal" size="lg">
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100">
                    <x-heroicon-s-pencil class="h-5 w-5 text-emerald-600" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Detail Nilai</h3>
                    <p class="text-xs text-slate-500 mt-0.5" x-text="selectedMhsNama"></p>
                </div>
            </div>
        </x-slot>
        <x-slot name="body">
            <div class="max-h-[60vh] overflow-y-auto space-y-4">
                {{-- Aspek Penilaian --}}
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs text-slate-500 border-b border-slate-200"><th class="pb-2 text-left font-medium">No</th><th class="pb-2 text-left font-medium">Aspek</th><th class="pb-2 text-center font-medium">Nilai (0-100)</th><th class="pb-2 text-left font-medium">Penguji</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="py-2 text-slate-500">1</td><td class="py-2 font-medium text-slate-700">Bacaan Al-Qur'an</td>
                                <td class="py-2 text-center"><input type="number" min="0" max="100" x-model="formData.nilai_bacaan_al_quran" class="w-20 rounded border border-slate-200 px-2 py-1 text-center text-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none" {{ in_array('bacaan', $tugas) ? '' : 'disabled' }}></td>
                                <td class="py-2 text-xs text-slate-400">{{ $jadwalTes->pengujiBacaanAlquran?->nama_lengkap ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-slate-500">2</td><td class="py-2 font-medium text-slate-700">Tajwid & Tahsin</td>
                                <td class="py-2 text-center"><input type="number" min="0" max="100" x-model="formData.nilai_tajwid_tahsin" class="w-20 rounded border border-slate-200 px-2 py-1 text-center text-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none" {{ in_array('tajwid', $tugas) ? '' : 'disabled' }}></td>
                                <td class="py-2 text-xs text-slate-400">{{ $jadwalTes->pengujiTajwidTahsin?->nama_lengkap ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-slate-500">3</td><td class="py-2 font-medium text-slate-700">Hafalan</td>
                                <td class="py-2 text-center"><input type="number" min="0" max="100" x-model="formData.nilai_hafalan" class="w-20 rounded border border-slate-200 px-2 py-1 text-center text-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none" {{ in_array('hafalan', $tugas) ? '' : 'disabled' }}></td>
                                <td class="py-2 text-xs text-slate-400">{{ $jadwalTes->pengujiHafalan?->nama_lengkap ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-slate-500">4</td><td class="py-2 font-medium text-slate-700">Wawancara</td>
                                <td class="py-2 text-center"><input type="number" min="0" max="100" x-model="formData.nilai_wawancara" class="w-20 rounded border border-slate-200 px-2 py-1 text-center text-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none" {{ in_array('wawancara', $tugas) ? '' : 'disabled' }}></td>
                                <td class="py-2 text-xs text-slate-400">{{ $jadwalTes->pengujiWawancara?->nama_lengkap ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Preview Hasil --}}
                <template x-if="previewResult">
                    <div class="rounded-lg border p-3" :class="previewResult.status === 'Lulus' ? 'border-emerald-200 bg-emerald-50' : (previewResult.status === 'Tidak Lulus' ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50')">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold text-slate-700">Total Nilai:</span>
                            <span class="font-bold text-lg" x-text="previewResult.total_nilai"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm mt-1">
                            <span class="font-semibold text-slate-700">Rata-rata:</span>
                            <span class="font-bold" x-text="previewResult.rata_rata"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm mt-1">
                            <span class="font-semibold text-slate-700">Status:</span>
                            <span class="font-bold px-2 py-0.5 rounded" :class="previewResult.status === 'Lulus' ? 'bg-emerald-100 text-emerald-700' : (previewResult.status === 'Tidak Lulus' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700')" x-text="previewResult.status"></span>
                        </div>
                    </div>
                </template>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="nilaiModal.close()">Tutup</button>
            @if($canInput)
            <button type="button" @click="submitNilai()" :disabled="saving" class="btn btn-success btn-sm gap-1.5">
                <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                <span>Simpan Nilai</span>
            </button>
            @endif
            @if($isCreator)
            <button type="button" @click="hitungHasil()" class="btn btn-outline btn-sm gap-1.5">
                <span>Hitung Hasil</span>
            </button>
            <template x-if="previewResult">
                <button type="button" @click="simpanHasil()" :disabled="saving" class="btn btn-primary btn-sm gap-1.5">
                    <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                    <span>Simpan Hasil</span>
                </button>
            </template>
            @endif
        </x-slot>
    </x-ui.modal>

    {{-- Modal: Review Pertimbangan --}}
    <x-ui.modal id="reviewModal" size="sm">
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Review Pertimbangan</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Konfirmasi keputusan</p>
                </div>
            </div>
        </x-slot>
        <x-slot name="body">
            <div class="space-y-4 py-2">
                <div class="rounded-lg border border-amber-100 bg-amber-50 p-4 text-sm text-amber-800">
                    <p>Ubah status menjadi: <strong class="text-lg" x-text="reviewPertimbanganAction === 'Lulus' ? 'LULUS' : 'TIDAK LULUS'"></strong></p>
                </div>
                <p class="text-xs text-slate-400">Keputusan ini hanya dapat dilakukan oleh Ketua Panitia.</p>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm text-slate-500 hover:text-slate-700 hover:bg-slate-100" onclick="reviewModal.close()">Batal</button>
            <button type="button" @click="confirmReview()" class="btn btn-sm" :class="reviewPertimbanganAction === 'Lulus' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-rose-600 text-white hover:bg-rose-700'">
                <span x-text="reviewPertimbanganAction === 'Lulus' ? 'Setujui' : 'Tolak'"></span>
            </button>
        </x-slot>
    </x-ui.modal>
</div>

@endsection

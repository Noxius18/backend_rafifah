@extends('layouts.app')

@section('content')

@php
    $userId = auth()->user()->id_panitia;
    $isCreator = $jadwalTes->penanggung_jawab == $userId;

    // Cek aspek yang ditugaskan ke panitia ini dari jadwalPenguji
    $tugas = [];
    foreach ($jadwalTes->jadwalPenguji as $jp) {
        if ($jp->id_panitia == $userId || $isCreator) {
            $tugas[] = $jp->aspek_penguji;
        }
    }

    $canInput = count($tugas) > 0;
    $isKetuaPanitia = auth()->user()->jabatan === 'Ketua Panitia';
    $isPanitia = auth()->user()->jabatan === 'Panitia';
    $isApproved = $jadwalTes->status_jadwal === 'Disetujui';

    // Hasil tes
    $hasilTesRow = $hasilTes->first();
    $statusHasil = $hasilTesRow ? $hasilTesRow->status : 'Belum Tes';
    $isPertimbangan = $statusHasil === 'Pertimbangan';
@endphp

<div x-data="{
    toast: { show: false, message: '', type: 'success', timer: null },
    showToast(message, type = 'success') {
        if (this.toast.timer) clearTimeout(this.toast.timer);
        Object.assign(this.toast, { message, type, show: true, timer: setTimeout(() => this.toast.show = false, 4000) });
    },
    saving: false,

    clampNilai(event, field) {
        const raw = event.target.value;
        if (raw === '' || raw === '-') return;
        let val = parseInt(raw);
        if (isNaN(val)) { event.target.value = ''; this.formData[field] = ''; return; }
        if (val > 100) { val = 100; }
        if (val < 0) { val = 0; }
        const clamped = String(val);
        if (clamped !== event.target.value) {
            event.target.value = clamped;
            this.formData[field] = clamped;
        }
    },

    formData: {
        nilai_bacaan_al_quran: '{{ $nilaiPerAspek['Bacaan Al-Quran']['nilai'] ?? '' }}',
        nilai_tajwid_tahsin: '{{ $nilaiPerAspek['Tajwid/Tahsin']['nilai'] ?? '' }}',
        nilai_hafalan: '{{ $nilaiPerAspek['Hafalan']['nilai'] ?? '' }}',
        nilai_wawancara: '{{ $nilaiPerAspek['Wawancara']['nilai'] ?? '' }}',
        catatan_bacaan_al_quran: '{{ $nilaiPerAspek['Bacaan Al-Quran']['catatan'] ?? '' }}',
        catatan_tajwid_tahsin: '{{ $nilaiPerAspek['Tajwid/Tahsin']['catatan'] ?? '' }}',
        catatan_hafalan: '{{ $nilaiPerAspek['Hafalan']['catatan'] ?? '' }}',
        catatan_wawancara: '{{ $nilaiPerAspek['Wawancara']['catatan'] ?? '' }}',
        catatan_ketua: '{{ $jadwalTes->catatan_ketua ?? '' }}',
    },

    get nilaiList() {
        return [
            parseInt(this.formData.nilai_bacaan_al_quran),
            parseInt(this.formData.nilai_tajwid_tahsin),
            parseInt(this.formData.nilai_hafalan),
            parseInt(this.formData.nilai_wawancara)
        ].filter(v => !isNaN(v));
    },

    get total() {
        const vals = this.nilaiList;
        return vals.length ? vals.reduce((a,b) => a+b, 0) : null;
    },

    get rataRata() {
        const vals = this.nilaiList;
        return vals.length ? Math.round(vals.reduce((a,b) => a+b, 0) / vals.length) : null;
    },

    get computedStatus() {
        const vals = this.nilaiList;
        if (vals.length < 4) return null;
        const below71Count = vals.filter(v => v < 71).length;
        if (below71Count === 0) return 'Lulus';
        if (below71Count === 1) return 'Pertimbangan';
        return 'Tidak Lulus';
    },

    async submitNilai() {
        this.saving = true;
        try {
            const res = await fetch('{{ route('hasil-tes.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ id_mahasantri: '{{ $jadwalTes->mahasantri?->id_mahasantri }}', id_jadwal: '{{ $jadwalTes->id_jadwal }}', ...this.formData }),
            });
            const data = await res.json();
            if (res.ok) { this.showToast(data.message || 'Nilai tersimpan', 'success'); setTimeout(() => location.reload(), 1200); }
            else { this.showToast(data.error || data.message || 'Gagal (' + res.status + ')', 'error'); }
        } catch(e) { this.showToast('Gagal menyimpan: ' + e.message, 'error'); }
        finally { this.saving = false; }
    },

    async simpanHasil() {
        if (this.nilaiList.length < 4) {
            this.showToast('Semua nilai aspek harus diisi terlebih dahulu', 'error');
            return;
        }
        this.saving = true;
        try {
            const res = await fetch('{{ route('hasil-tes.simpan-hasil') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ id_mahasantri: '{{ $jadwalTes->mahasantri?->id_mahasantri }}', id_jadwal: '{{ $jadwalTes->id_jadwal }}', ...this.formData }),
            });
            const data = await res.json();
            if (res.ok) { this.showToast(data.message, 'success'); setTimeout(() => location.reload(), 1200); }
            else { this.showToast(data.error || data.message || 'Gagal (' + res.status + ')', 'error'); }
        } catch(e) { this.showToast('Gagal menyimpan: ' + e.message, 'error'); }
        finally { this.saving = false; }
    },

    // Review Ketua Panitia
    reviewId: '{{ $hasilTesRow?->id_hasil }}',
    async confirmReview(action) {
        const payload = {
            status: action,
            nilai_bacaan_al_quran: this.formData.nilai_bacaan_al_quran || 0,
            nilai_tajwid_tahsin: this.formData.nilai_tajwid_tahsin || 0,
            nilai_hafalan: this.formData.nilai_hafalan || 0,
            nilai_wawancara: this.formData.nilai_wawancara || 0,
            catatan_ketua: this.formData.catatan_ketua || '',
            catatan_bacaan_al_quran: this.formData.catatan_bacaan_al_quran || '',
            catatan_tajwid_tahsin: this.formData.catatan_tajwid_tahsin || '',
            catatan_hafalan: this.formData.catatan_hafalan || '',
            catatan_wawancara: this.formData.catatan_wawancara || '',
        };
        try {
            const res = await fetch('/hasil-tes/' + this.reviewId + '/review', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (res.ok) { this.showToast(data.message, 'success'); setTimeout(() => location.reload(), 1000); }
            else { this.showToast(data.error || data.message || 'Gagal (' + res.status + ')', 'error'); }
        } catch(e) { this.showToast('Gagal: ' + e.message, 'error'); }
    }
}"
x-init="@if(session('success')) showToast('{{ session('success') }}') @endif @if(session('error')) showToast('{{ session('error') }}', 'error') @endif">

    <x-ui.toast />
    <x-ui.sidebar>
        <section class="space-y-5 px-1 py-2">

            {{-- HEADER --}}
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-1 h-7 w-1 rounded-full bg-emerald-500"></div>
                    <div>
                        <h1 class="text-xl font-semibold text-black">{{ $jadwalTes->mahasantri?->nama_lengkap ?? 'Unknown' }}</h1>
                        <div class="flex items-center gap-2 mt-0.5">
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

            {{-- NOTICE: Mode Review Ketua Panitia --}}
            @if($isKetuaPanitia && $isPertimbangan)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                <div class="flex items-start gap-2">
                    <svg class="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">Mode Review — Pertimbangan</p>
                        <p class="text-xs text-amber-700 mt-0.5">Anda dapat mengubah nilai aspek, menambahkan catatan, dan memberikan keputusan final (Lulus / Tidak Lulus) untuk mahasantri ini.</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- PENGUJI INFO --}}
            <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    <span class="text-sm font-semibold text-indigo-700">Penguji</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($nilaiPerAspek as $aspek => $data)
                        <div class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-indigo-600 ring-1 ring-indigo-200">
                            <span class="text-indigo-400">•</span>
                            <span>{{ $aspek }}:</span>
                            <span class="font-bold">{{ $data['penguji'] ?? '-' }}</span>
                            @if($data['id_panitia'] == $userId)
                                <span class="text-[10px] text-indigo-400 italic">(Anda)</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if($isCreator && !$isKetuaPanitia)
                    <p class="mt-2 text-xs text-indigo-500">Anda pembuat jadwal — bisa input semua aspek + simpan hasil final.</p>
                @endif
                @if(!$isCreator && !$isKetuaPanitia && count($tugas) > 0)
                    <p class="mt-2 text-xs text-indigo-500">Anda hanya bisa input aspek yang ditugaskan.</p>
                @endif
            </div>

            {{-- CARD INPUT NILAI + CATATAN PER ASPEK --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                @foreach([
                    ['key' => 'Bacaan Al-Quran', 'field' => 'nilai_bacaan_al_quran', 'catatanField' => 'catatan_bacaan_al_quran', 'label' => 'Bacaan Al-Qur\'an'],
                    ['key' => 'Tajwid/Tahsin', 'field' => 'nilai_tajwid_tahsin', 'catatanField' => 'catatan_tajwid_tahsin', 'label' => 'Tajwid & Tahsin'],
                    ['key' => 'Hafalan', 'field' => 'nilai_hafalan', 'catatanField' => 'catatan_hafalan', 'label' => 'Hafalan'],
                    ['key' => 'Wawancara', 'field' => 'nilai_wawancara', 'catatanField' => 'catatan_wawancara', 'label' => 'Wawancara'],
                ] as $aspek)
                @php
                    $canEditThis = ($isApproved && (in_array($aspek['key'], $tugas) || $isCreator)) || $isKetuaPanitia;
                    $nilaiAwal = $nilaiPerAspek[$aspek['key']]['nilai'] ?? '';
                    $isNilai70 = ($nilaiAwal === 70 || $nilaiAwal === '70');
                    $pengujiNama = $nilaiPerAspek[$aspek['key']]['penguji'] ?? '-';
                @endphp
                <div class="rounded-xl border border-slate-200 bg-white p-4 transition hover:shadow-sm flex flex-col gap-3
                    {{ $canEditThis ? '' : 'opacity-70' }}
                    {{ $isKetuaPanitia && $isPertimbangan && $isNilai70 ? 'ring-2 ring-amber-300 bg-amber-50/30' : '' }}">
                    {{-- Header card --}}
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-700">{{ $aspek['label'] }}</h3>
                        @if($isKetuaPanitia && $isPertimbangan && $isNilai70)
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700">Perlu review</span>
                        @elseif($canEditThis)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700">Tugas Anda</span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-400">Read-only</span>
                        @endif
                    </div>

                    {{-- Input nilai --}}
                    <input type="number" min="0" max="100"
                        x-model="formData.{{ $aspek['field'] }}"
                        @input="clampNilai($event, '{{ $aspek['field'] }}')"
                        class="w-full rounded-lg border-2 px-3 py-2.5 text-center text-lg font-bold transition outline-none
                            @if($canEditThis)
                                border-slate-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100
                            @else
                                border-slate-100 bg-slate-50 text-slate-500 cursor-not-allowed
                            @endif"
                        {{ $canEditThis ? '' : 'disabled' }}
                        placeholder="0-100" />

                    {{-- Penguji --}}
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>Penguji:</span>
                        <span class="font-medium text-slate-500">{{ $pengujiNama }}</span>
                    </div>

                    {{-- Catatan per aspek --}}
                    <div>
                        <label class="text-[11px] font-medium text-slate-400 mb-1 block">Catatan</label>
                        <textarea x-model="formData.{{ $aspek['catatanField'] }}" rows="2"
                            class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs transition outline-none resize-none
                                @if($canEditThis)
                                    focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100
                                @else
                                    bg-slate-50 text-slate-500 cursor-not-allowed
                                @endif"
                            placeholder="Catatan untuk {{ $aspek['label'] }}..."
                            {{ $canEditThis ? '' : 'disabled' }}></textarea>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- CATATAN KETUA (hanya tampil untuk Ketua Panitia saat Pertimbangan) --}}
            @if($isKetuaPanitia && $isPertimbangan)
            <div class="rounded-xl border border-amber-200 bg-white p-4">
                <label class="flex items-center gap-2 mb-2">
                    <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                    <span class="text-sm font-semibold text-slate-700">Catatan Ketua Panitia</span>
                </label>
                <textarea x-model="formData.catatan_ketua" rows="3"
                    class="w-full rounded-lg border border-amber-200 px-3 py-2 text-sm transition outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100 resize-none"
                    placeholder="Tuliskan catatan hasil review..."></textarea>
            </div>
            @endif

            {{-- ACTION BUTTONS --}}
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
                    @else
                        @if($isPanitia)
                            Isi semua aspek, lalu simpan hasil.
                        @endif
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if($isPanitia && !$isPertimbangan && $isApproved)
                        <button type="button" @click="submitNilai()" :disabled="saving"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 active:scale-95">
                            <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            Simpan Nilai
                        </button>
                    @endif
                    @if(($isCreator || $isPanitia) && !$isPertimbangan && $isApproved)
                        <button type="button" @click="simpanHasil()" :disabled="saving"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95">
                            <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Simpan Hasil
                        </button>
                    @endif
                    @if($isKetuaPanitia && $isPertimbangan)
                        <button type="button" @click="confirmReview('Lulus')" :disabled="saving"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95">
                            <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            ✅ Setujui (Lulus)
                        </button>
                        <button type="button" @click="confirmReview('Tidak Lulus')" :disabled="saving"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-4 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50 active:scale-95">
                            <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            ❌ Tolak (Tidak Lulus)
                        </button>
                    @endif
                </div>
            </div>

        </section>
    </x-ui.sidebar>
</div>

@endsection
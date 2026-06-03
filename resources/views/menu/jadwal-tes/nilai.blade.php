@extends('layouts.app')

@section('content')

<div x-data="{
    toast: { show: false, message: '', type: 'success', timer: null },
    showToast(message, type = 'success') {
        if (this.toast.timer) clearTimeout(this.toast.timer);
        Object.assign(this.toast, { message, type, show: true });
        this.toast.timer = setTimeout(() => this.toast.show = false, 4000);
    },
    selectedMhs: null,
    selectedMhsNama: '',
    formData: {
        nilai_tajwid: '',
        nilai_tahsin: '',
        nilai_kelancaran: '',
        nilai_wawancara: '',
        catatan_penguji: '',
    },
    saving: false,

    openNilaiModal(mhs, mhsNama, hasil = null) {
        this.selectedMhs = mhs;
        this.selectedMhsNama = mhsNama;
        if (hasil) {
            this.formData.nilai_tajwid = hasil.nilai_tajwid || '';
            this.formData.nilai_tahsin = hasil.nilai_tahsin || '';
            this.formData.nilai_kelancaran = hasil.nilai_kelancaran || '';
            this.formData.nilai_wawancara = hasil.nilai_wawancara || '';
            this.formData.catatan_penguji = hasil.catatan_penguji || '';
        } else {
            this.formData.nilai_tajwid = '';
            this.formData.nilai_tahsin = '';
            this.formData.nilai_kelancaran = '';
            this.formData.nilai_wawancara = '';
            this.formData.catatan_penguji = '';
        }
        nilaiModal.showModal();
    },

    async submitNilai() {
        this.saving = true;
        try {
            const res = await fetch('{{ route('hasil-tes.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    id_mahasantri: this.selectedMhs,
                    id_jadwal: '{{ $jadwalTes->id_jadwal }}',
                    ...this.formData,
                }),
            });
            const data = await res.json();
            if (res.ok) {
                this.showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showToast(data.message || 'Gagal menyimpan nilai', 'error');
            }
        } catch(e) {
            this.showToast('Gagal menyimpan nilai', 'error');
        } finally {
            this.saving = false;
        }
    },

    reviewPertimbanganId: null,
    reviewPertimbanganAction: null,

    openReviewModal(hasilId, action) {
        this.reviewPertimbanganId = hasilId;
        this.reviewPertimbanganAction = action;
        reviewModal.showModal();
    },

    async confirmReview() {
        const hasilId = this.reviewPertimbanganId;
        const action = this.reviewPertimbanganAction;
        try {
            const res = await fetch('/hasil-tes/' + hasilId + '/review', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status: action }),
            });
            const data = await res.json();
            if (res.ok) {
                this.showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showToast(data.message || 'Gagal', 'error');
            }
        } catch(e) {
            this.showToast('Gagal memperbarui status', 'error');
        }
    }
}"
x-init="
    @if(session('success')) showToast('{{ session('success') }}') @endif
    @if(session('error'))   showToast('{{ session('error') }}', 'error') @endif
">

    <x-ui.toast />

    <x-ui.sidebar>
        <section class="space-y-6 px-1 py-2">

            {{-- Header --}}
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-1 h-7 w-1 rounded-full bg-emerald-500"></div>
                    <div>
                        <h1 class="text-xl font-semibold text-slate-800">Nilai — {{ $jadwalTes->mahasantri?->nama_lengkap ?? 'Unknown' }}</h1>
                        <p class="text-sm text-slate-400">{{ $jadwalTes->mahasantri?->id_mahasantri ?? '' }} &bull; {{ \Carbon\Carbon::parse($jadwalTes->tanggal)->format('d F Y') }} @if($jadwalTes->jam) &bull; {{ \Carbon\Carbon::parse($jadwalTes->jam)->format('H:i') }} @endif</p>
                    </div>
                </div>
                <a href="{{ route('seleksi.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    <x-heroicon-s-arrow-left class="h-4 w-4" />
                    Kembali
                </a>
            </div>

            {{-- Info Penguji --}}
            <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-sm text-indigo-700">
                <p class="font-medium">Penguji:</p>
                @php
                    $pengujiItems = [];
                    if ($jadwalTes->pengujiTajwid) $pengujiItems[] = [$jadwalTes->pengujiTajwid->nama_lengkap, 'Tajwid'];
                    if ($jadwalTes->pengujiTahsin) $pengujiItems[] = [$jadwalTes->pengujiTahsin->nama_lengkap, 'Tahsin'];
                    if ($jadwalTes->pengujiKelancaran) $pengujiItems[] = [$jadwalTes->pengujiKelancaran->nama_lengkap, 'Kelancaran'];
                    if ($jadwalTes->pengujiWawancara) $pengujiItems[] = [$jadwalTes->pengujiWawancara->nama_lengkap, 'Wawancara dan Sikap'];
                @endphp
                @if (empty($pengujiItems))
                    <p class="text-xs text-indigo-400">Belum ditentukan</p>
                @else
                    <div class="mt-1 flex flex-wrap gap-2">
                        @foreach ($pengujiItems as $item)
                            <span class="inline-block rounded-md bg-white px-2 py-0.5 text-xs font-medium text-indigo-600 ring-1 ring-indigo-200">
                                {{ $item[0] }} <span class="text-indigo-400">({{ $item[1] }})</span>
                            </span>
                        @endforeach
                    </div>
                @endif
                <p class="mt-2 text-xs text-indigo-400">
                    @if(auth()->user()->jabatan === 'Panitia')
                        Anda dapat menginput dan mengedit nilai.
                    @elseif(auth()->user()->jabatan === 'Pengawas')
                        Anda dapat melihat nilai dan mereview status Pertimbangan.
                    @endif
                </p>
            </div>

            {{-- Tabel Mahasantri --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-100 text-left">
                            <tr class="text-xs font-medium text-slate-400">
                                <th class="px-4 py-3">No</th>
                                <th class="px-4 py-3">Nama Mahasantri</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($mahasantris as $index => $m)
                                @php
                                    $hasil = $hasilTes->get($m->id_mahasantri);
                                @endphp
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-slate-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-700">
                                                {{ strtoupper(substr($m->nama_lengkap, 0, 1)) }}
                                            </div>
                                            <span class="font-medium text-slate-700">{{ $m->nama_lengkap }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($hasil)
                                            @if ($hasil->status === 'Lulus')
                                                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Lulus</span>
                                            @elseif ($hasil->status === 'Tidak Lulus')
                                                <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200">Tidak Lulus</span>
                                            @elseif ($hasil->status === 'Pertimbangan')
                                                <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">Pertimbangan</span>
                                            @else
                                                <span class="rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-500 ring-1 ring-slate-200">Belum Tes</span>
                                            @endif
                                            @if ($hasil->total_nilai)
                                                <span class="ml-1 text-xs text-slate-400">({{ $hasil->total_nilai }})</span>
                                            @endif
                                        @else
                                            <span class="rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-500 ring-1 ring-slate-200">Belum Tes</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-0.5">
                                            {{-- Tombol Lihat/Detail (semua role bisa) --}}
                                            <button type="button"
                                                @click="openNilaiModal('{{ $m->id_mahasantri }}', '{{ $m->nama_lengkap }}', @js($hasil))"
                                                class="inline-flex items-center justify-center rounded-md p-2 text-emerald-600 transition hover:bg-emerald-50"
                                                title="Lihat Nilai">
                                                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z'/><path stroke-linecap='round' stroke-linejoin='round' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'/></svg>
                                            </button>

                                            {{-- Review Pertimbangan — hanya Pengawas --}}
                                            @if(auth()->user()->jabatan === 'Pengawas' && $hasil && $hasil->status === 'Pertimbangan')
                                                <button type="button"
                                                    @click="openReviewModal('{{ $hasil->id_hasil }}', 'Lulus')"
                                                    class="inline-flex items-center justify-center rounded-md p-2 text-emerald-600 transition hover:bg-emerald-50"
                                                    title="Setujui">
                                                    <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'/></svg>
                                                </button>
                                                <button type="button"
                                                    @click="openReviewModal('{{ $hasil->id_hasil }}', 'Tidak Lulus')"
                                                    class="inline-flex items-center justify-center rounded-md p-2 text-rose-600 transition hover:bg-rose-50"
                                                    title="Tolak">
                                                    <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z'/></svg>
                                                </button>
                                            @endif

                                            {{-- Input/Edit Nilai — hanya Panitia --}}
                                            @if(auth()->user()->jabatan === 'Panitia')
                                                @if (!$hasil || $hasil->status !== 'Pertimbangan')
                                                    <button type="button"
                                                        @click="openNilaiModal('{{ $m->id_mahasantri }}', '{{ $m->nama_lengkap }}', @js($hasil))"
                                                        class="inline-flex items-center justify-center rounded-md p-2 transition hover:bg-emerald-50"
                                                        :class="'{{ $hasil ? 'text-sky-600' : 'text-emerald-600' }}'"
                                                        title="{{ $hasil ? 'Edit Nilai' : 'Input Nilai' }}">
                                                        <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'/></svg>
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center text-sm text-slate-400">
                                        Belum ada mahasantri terdaftar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-400">
                    Menampilkan {{ $mahasantris->count() }} mahasantri
                </div>
            </div>

        </section>
    </x-ui.sidebar>

    {{-- Modal: Input/Edit Nilai --}}
    <x-ui.modal id="nilaiModal" size="lg">
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100">
                    <x-heroicon-s-pencil class="h-5 w-5 text-emerald-600" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-800" x-text="'Detail Nilai'"></h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Mahasantri: <span class="font-medium" x-text="selectedMhsNama"></span>
                    </p>
                </div>
            </div>
        </x-slot>

        <x-slot name="body">
            <div class="max-h-[65vh] overflow-y-auto -mr-2 pr-2 space-y-4">
                {{-- Aspek Penilaian --}}
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-slate-500 border-b border-slate-200">
                                <th class="pb-2 text-left font-medium">No</th>
                                <th class="pb-2 text-left font-medium">Aspek Penilaian</th>
                                <th class="pb-2 text-center font-medium">Nilai (0-100)</th>
                                <th class="pb-2 text-left font-medium">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="py-2 text-slate-500">1</td>
                                <td class="py-2 font-medium text-slate-700">Tajwid</td>
                                <td class="py-2 text-center">
                                    <input type="number" min="0" max="100" x-model="formData.nilai_tajwid"
                                        class="w-20 rounded border border-slate-200 px-2 py-1 text-center text-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none"
                                        {{ auth()->user()->jabatan !== 'Panitia' ? 'disabled' : '' }}>
                                </td>
                                <td class="py-2 text-xs text-slate-400">Nilai ilmu tajwid</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-slate-500">2</td>
                                <td class="py-2 font-medium text-slate-700">Tahsin</td>
                                <td class="py-2 text-center">
                                    <input type="number" min="0" max="100" x-model="formData.nilai_tahsin"
                                        class="w-20 rounded border border-slate-200 px-2 py-1 text-center text-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none"
                                        {{ auth()->user()->jabatan !== 'Panitia' ? 'disabled' : '' }}>
                                </td>
                                <td class="py-2 text-xs text-slate-400">Nilai tahsin bacaan</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-slate-500">3</td>
                                <td class="py-2 font-medium text-slate-700">Kelancaran Bacaan</td>
                                <td class="py-2 text-center">
                                    <input type="number" min="0" max="100" x-model="formData.nilai_kelancaran"
                                        class="w-20 rounded border border-slate-200 px-2 py-1 text-center text-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none"
                                        {{ auth()->user()->jabatan !== 'Panitia' ? 'disabled' : '' }}>
                                </td>
                                <td class="py-2 text-xs text-slate-400">Nilai kelancaran membaca</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-slate-500">4</td>
                                <td class="py-2 font-medium text-slate-700">Wawancara</td>
                                <td class="py-2 text-center">
                                    <input type="number" min="0" max="100" x-model="formData.nilai_wawancara"
                                        class="w-20 rounded border border-slate-200 px-2 py-1 text-center text-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none"
                                        {{ auth()->user()->jabatan !== 'Panitia' ? 'disabled' : '' }}>
                                </td>
                                <td class="py-2 text-xs text-slate-400">Nilai hasil wawancara</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Rata-rata Live --}}
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-slate-700">Rata-rata:</span>
                        <span class="text-lg font-bold text-emerald-600" x-text="
                            (() => {
                                const vals = [formData.nilai_tajwid, formData.nilai_tahsin, formData.nilai_kelancaran, formData.nilai_wawancara]
                                    .map(v => parseInt(v)).filter(v => !isNaN(v));
                                return vals.length ? Math.round(vals.reduce((a,b) => a+b, 0) / vals.length) : '-';
                            })()
                        "></span>
                    </div>
                </div>

                {{-- Catatan Penguji --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold text-sm text-slate-700">Catatan Penguji</span>
                    </label>
                    <textarea x-model="formData.catatan_penguji" rows="3"
                        class="textarea textarea-bordered text-sm w-full focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                        placeholder="Catatan untuk mahasantri ini..."
                        {{ auth()->user()->jabatan !== 'Panitia' ? 'disabled' : '' }}></textarea>
                </div>

                {{-- Kriteria Nilai --}}
                <div class="rounded-lg border border-amber-100 bg-amber-50 p-3 text-xs text-amber-700">
                    <p class="font-medium">Kriteria Nilai:</p>
                    <p>90-100: Sangat Baik &bull; 80-89: Baik &bull; 70-79: Cukup &bull; <70: Tidak Lulus</p>
                    <p class="mt-1">Jika ada nilai aspek <strong>70</strong>, status akan menjadi <strong>"Pertimbangan"</strong> dan perlu direview Pengawas.</p>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="nilaiModal.close()">Tutup</button>
            @if(auth()->user()->jabatan === 'Panitia')
            <button type="button" @click="submitNilai()" :disabled="saving" class="btn btn-success btn-sm gap-1.5">
                <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Nilai'"></span>
            </button>
            @endif
        </x-slot>
    </x-ui.modal>

    {{-- Modal: Review Pertimbangan --}}
    <x-ui.modal id="reviewModal" size="sm">
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Review Pertimbangan</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Konfirmasi keputusan untuk mahasantri ini</p>
                </div>
            </div>
        </x-slot>

        <x-slot name="body">
            <div class="space-y-4 py-2">
                <div class="rounded-lg border border-amber-100 bg-amber-50 p-4 text-sm text-amber-800">
                    <p>Apakah Anda yakin ingin mengubah status mahasantri ini menjadi:</p>
                    <p class="mt-2 text-center text-lg font-bold" x-text="reviewPertimbanganAction === 'Lulus' ? '✅ LULUS' : '❌ TIDAK LULUS'"></p>
                </div>
                <p class="text-xs text-slate-400">Keputusan ini hanya dapat dilakukan oleh Pengawas.</p>
            </div>
        </x-slot>

        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm text-slate-500 hover:text-slate-700 hover:bg-slate-100" onclick="reviewModal.close()">Batal</button>
            <button type="button" @click="confirmReview()"
                class="btn btn-sm gap-1.5"
                :class="reviewPertimbanganAction === 'Lulus' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-rose-600 text-white hover:bg-rose-700'">
                <span x-text="reviewPertimbanganAction === 'Lulus' ? '✅ Setujui' : '❌ Tolak'"></span>
            </button>
        </x-slot>
    </x-ui.modal>

</div>

@endsection
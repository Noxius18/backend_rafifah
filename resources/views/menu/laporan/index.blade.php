@extends('layouts.app')

@section('content')

<div x-data="{
    toast: { show: false, message: '', type: 'success', timer: null },
    showToast(message, type = 'success') {
        if (this.toast.timer) clearTimeout(this.toast.timer);
        Object.assign(this.toast, { message, type, show: true });
        this.toast.timer = setTimeout(() => this.toast.show = false, 4000);
    },
}">

    <x-ui.toast />

    <x-ui.sidebar>
        <section class="space-y-6 px-1 py-2">

            {{-- Header --}}
            <div class="flex items-start gap-3">
                <div class="mt-1 h-7 w-1 rounded-full bg-emerald-500"></div>
                <div>
                    <h1 class="text-xl font-semibold text-slate-800">Laporan</h1>
                    <p class="text-sm text-slate-400">Cetak laporan nilai dan ringkasan keseluruhan.</p>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium text-slate-400">Total Mahasantri</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">{{ $summary['total_mahasantri'] }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-xs font-medium text-emerald-600">Lulus</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $summary['total_lulus'] }}</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                    <p class="text-xs font-medium text-rose-600">Tidak Lulus</p>
                    <p class="mt-1 text-2xl font-bold text-rose-700">{{ $summary['total_tidak_lulus'] }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs font-medium text-amber-600">Pertimbangan</p>
                    <p class="mt-1 text-2xl font-bold text-amber-700">{{ $summary['total_pertimbangan'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-medium text-slate-500">Belum Tes</p>
                    <p class="mt-1 text-2xl font-bold text-slate-600">{{ $summary['total_belum_tes'] }}</p>
                </div>
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                    <p class="text-xs font-medium text-indigo-600">Jadwal Tes</p>
                    <p class="mt-1 text-2xl font-bold text-indigo-700">{{ $summary['total_jadwal'] }}</p>
                </div>
            </div>

            {{-- Aksi Cetak --}}
            <div class="grid gap-4 md:grid-cols-2">
                {{-- Cetak Nilai --}}
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-slate-800">Cetak Nilai Mahasantri</h3>
                            <p class="mt-0.5 text-xs text-slate-400">Download PDF nilai per mahasantri, bisa filter per jadwal tes.</p>
                            <form action="{{ route('laporan.cetak-nilai') }}" method="GET" class="mt-3 flex items-center gap-2">
                                <select name="id_jadwal" class="select select-bordered select-sm w-full max-w-xs">
                                    <option value="">Semua Jadwal</option>
                                    @foreach ($jadwals as $j)
                                        <option value="{{ $j->id_jadwal }}">{{ $j->periode }} — {{ \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm bg-emerald-600 text-white hover:bg-emerald-700 border-none gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    Download PDF
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Cetak Overall --}}
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-slate-800">Cetak Laporan Overall</h3>
                            <p class="mt-0.5 text-xs text-slate-400">Download PDF ringkasan keseluruhan: jumlah lulus, tidak lulus, per jadwal, dll.</p>
                            <a href="{{ route('laporan.cetak-overall') }}" class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Download PDF Overall
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </x-ui.sidebar>

</div>

@endsection
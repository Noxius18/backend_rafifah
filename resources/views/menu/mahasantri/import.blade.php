@extends('layouts.app')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="text-center">
        <x-heroicon-s-arrow-up-tray class="mx-auto h-12 w-12 text-slate-300" />
        <h2 class="mt-4 text-lg font-semibold text-slate-700">Import Data Mahasantri</h2>
        <p class="mt-1 text-sm text-slate-400">Gunakan tombol Upload Excel di halaman utama.</p>
        <a href="{{ route('mahasantri.index') }}"
           class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
            <x-heroicon-s-arrow-left class="h-4 w-4" />
            Kembali ke Kelola Mahasantri
        </a>
    </div>
</div>
@endsection
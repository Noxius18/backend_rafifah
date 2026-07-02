@extends('layouts.app')

@section('content')
@php
    $pageConfig = [
        'gelombangs' => $gelombangs->map(fn($gelombang) => [
            'nama' => $gelombang->nama,
            'start_date' => $gelombang->getRawOriginal('start_date'),
            'end_date' => $gelombang->getRawOriginal('end_date'),
        ])->values()->all(),
        'routes' => [
            'updateSingle' => route('seleksi.update', '__ID__'),
            'updateByDate' => route('seleksi.update-by-date', '__TANGGAL__'),
        ],
        'unscheduledCount' => count(session('unscheduledMahasantri', [])),
    ];
@endphp

<div x-data="jadwalTesIndex(@js($pageConfig))" x-init="init()">
    <x-ui.sidebar>
        <section class="space-y-6 px-1 py-2">
            @include('menu.jadwal-tes.partials.index.header-actions')

            <div class="space-y-3">
                @if(session('success'))
                    <x-ui.alert type="success" :alert="session('success')" />
                @endif

                @if(session('error'))
                    <x-ui.alert type="error" :alert="session('error')" />
                @endif
            </div>

            <div class="overflow-hidden rounded-xl border border-black/20 bg-white">
                <x-ui.data-table :rows="$rows" :columns="$columns" :total="$jadwals->total()" empty-message="Belum ada jadwal" add-label="Buat Jadwal" />
                <x-ui.pagination :paginator="$jadwals" alwaysShow="true" />
            </div>
        </section>
    </x-ui.sidebar>

    @include('menu.jadwal-tes.partials.index.add-modal')
    @include('menu.jadwal-tes.partials.index.send-bulk-modal')
    @include('menu.jadwal-tes.partials.index.edit-modal')
    @include('menu.jadwal-tes.partials.index.review-modal')
    @include('menu.jadwal-tes.partials.index.edit-by-date-modals')
    @include('menu.jadwal-tes.partials.index.unscheduled-modal')
</div>
@endsection

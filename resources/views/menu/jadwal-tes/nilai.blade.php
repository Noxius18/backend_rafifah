@extends('layouts.app')

@section('content')
@php
    $statusHasil = $pageState['statusHasil'];
    $isPertimbangan = $pageState['isPertimbangan'];
    $isKetuaPanitia = $pageState['isKetuaPanitia'];
    $isPanitia = $pageState['isPanitia'];
    $isApproved = $pageState['isApproved'];
    $isCreator = $pageState['isCreator'];
    $pageConfig = [
        'csrfToken' => csrf_token(),
        'idMahasantri' => $jadwalTes->mahasantri?->id_mahasantri,
        'idJadwal' => $jadwalTes->id_jadwal,
        'formData' => $pageState['formData'],
        'reviewId' => $pageState['reviewId'],
        'routes' => [
            'store' => route('hasil-tes.store'),
            'simpanHasil' => route('hasil-tes.simpan-hasil'),
            'review' => url('/hasil-tes/__ID__/review'),
        ],
    ];
@endphp

<div
    x-data="jadwalTesNilai(@js($pageConfig))"
    x-init="
        @if(session('success')) showToast(@js(session('success'))) @endif
        @if(session('error')) showToast(@js(session('error')), 'error') @endif
    "
>
    <x-ui.toast />
    <x-ui.sidebar>
        <section class="space-y-5 px-1 py-2">
            @include('menu.jadwal-tes.partials.nilai.header')
            @includeWhen($isKetuaPanitia && $isPertimbangan, 'menu.jadwal-tes.partials.nilai.review-notice')
            @include('menu.jadwal-tes.partials.nilai.penguji')
            @include('menu.jadwal-tes.partials.nilai.aspek-grid')
            @includeWhen($isKetuaPanitia && $isPertimbangan, 'menu.jadwal-tes.partials.nilai.catatan-ketua')
            @include('menu.jadwal-tes.partials.nilai.actions')
        </section>
    </x-ui.sidebar>
</div>
@endsection

@extends('layouts.app')

@section('content')
@php
    $m = $mahasantri;
    $pageConfig = [
        'previewDocs' => $previewDocs,
        'retryUrlTemplate' => url('/berkas/__ID__/retry-download'),
        'updateUrlTemplate' => url('/berkas/__ID__'),
        'deleteAction' => route('mahasantri.destroy', $m->id_mahasantri),
        'deleteName' => $m->nama_lengkap,
        'nik' => $m->nik,
        'nisn' => $m->nisn,
    ];
@endphp

<div
    x-data="mahasantriShow(@js($pageConfig))"
    x-init="
        @if(session('success')) showToast(@js(session('success'))) @endif
        @if(session('error')) showToast(@js(session('error')), 'error') @endif
        @if($errors->any()) $nextTick(() => document.getElementById('editModal')?.showModal()) @endif
    "
>
    <x-ui.toast />

    <x-ui.sidebar>
        <section class="space-y-6 px-1 py-2">
            @include('menu.mahasantri.partials.show.header')
            @include('menu.mahasantri.partials.show.biodata')
            @include('menu.mahasantri.partials.show.orangtua')
            @include('menu.mahasantri.partials.show.dokumen')
        </section>
    </x-ui.sidebar>

    @include('menu.mahasantri.partials.show.edit-modal')
    <x-ui.modal-confirm id="deleteModal" title="Konfirmasi Hapus" body-text="Apakah Anda yakin ingin menghapus data mahasantri" confirm-label="Hapus" />
    @include('menu.mahasantri.partials.show.preview-modal')
</div>
@endsection

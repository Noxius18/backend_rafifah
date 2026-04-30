@extends('layouts.app')

@section('content')
  <x-ui.sidebar>
    <section class="space-y-4">
      <div class="rounded-2xl border border-emerald-200 bg-white/90 p-6 shadow-sm shadow-emerald-200">
        <h1 class="text-2xl font-semibold text-emerald-900">Selamat Datang di Dashboard</h1>
        <p class="mt-2 text-sm text-slate-600">Gunakan menu di sidebar untuk mengakses fitur utama.</p>
      </div>
    </section>
  </x-ui.sidebar>
@endsection
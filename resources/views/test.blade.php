@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center">

    {{-- DaisyUI component --}}
    <div class="card w-96 bg-base shadow-xl">
        <div class="card-body">
            <h2 class="card-title">Laravel 13 🚀</h2>
            <p>AlpineJS + DaisyUI + Tailwind 4</p>

            {{-- Alpine JS --}}
            <div x-data="{ count: 0 }">
                <p class="text-lg font-bold">Count: <span x-text="count"></span></p>
                <button class="btn btn-primary" @click="count++">Tambah</button>
            </div>
        </div>
    </div>
</div>
@endsection
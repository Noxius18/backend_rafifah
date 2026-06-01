@extends('layouts.app')

@section('content')

<div x-data="{
    toast: { show: false, message: '', type: 'success', timer: null },
    showToast(message, type = 'success') {
        if (this.toast.timer) clearTimeout(this.toast.timer);
        Object.assign(this.toast, { message, type, show: true });
        this.toast.timer = setTimeout(() => this.toast.show = false, 4000);
    },
}"
x-init="
    @if(session('success')) showToast('{{ session('success') }}') @endif
    @if(session('error')) showToast('{{ session('error') }}', 'error') @endif
    @if($errors->any()) $nextTick(() => {
        const firstError = document.querySelector('.input-error');
        if(firstError) firstError.scrollIntoView({behavior: 'smooth', block: 'center'});
    }) @endif
">

    <x-ui.toast />
    <x-ui.sidebar>
        <section class="space-y-6 px-4 py-4">
            <div class="flex items-start gap-3">
                <div class="mt-1 h-7 w-1 rounded-full bg-emerald-500"></div>
                <div>
                    <h1 class="text-xl font-semibold text-black">Pengaturan Gelombang</h1>
                    <p class="text-sm text-black">Atur rentang pendaftaran untuk setiap gelombang.</p>
                </div>
            </div>

            {{-- Card per gelombang --}}
            @foreach($gelombang as $g)
            <div class="overflow-hidden rounded-xl border border-black/20 bg-white p-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-sm font-bold text-emerald-700 ring-1 ring-emerald-200">{{ $g->nomor }}</span>
                        <h2 class="text-base font-semibold text-black">{{ $g->nama }}</h2>
                    </div>
                    @if($g->updated_by)
                        <span class="text-xs text-black/40">
                            Diedit oleh {{ $g->updatedBy->nama_lengkap ?? '—' }}
                            {{ $g->updated_at ? $g->updated_at->diffForHumans() : '' }}
                        </span>
                    @endif
                </div>

                <form method="POST" action="{{ route('gelombang.update', $g->nomor) }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    @csrf
                    @method('PUT')

                    <div class="flex-1">
                        <x-ui.form-input
                            name="start_date"
                            label="Tanggal Mulai"
                            type="date"
                            value="{{ $g->start_date->format('Y-m-d') }}"
                            required
                        />
                    </div>

                    <div class="flex items-center text-slate-400 px-2 pt-5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </div>

                    <div class="flex-1">
                        <x-ui.form-input
                            name="end_date"
                            label="Tanggal Akhir"
                            type="date"
                            value="{{ $g->end_date->format('Y-m-d') }}"
                            required
                        />
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95 whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Simpan Perubahan
                    </button>
                </form>

                {{-- Error display --}}
                @if($errors->has('start_date') || $errors->has('end_date'))
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-red-500 flex items-center gap-1 mt-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            {{ $error }}
                        </p>
                    @endforeach
                @endif
            </div>
            @endforeach

            <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p>Gelombang sudah fix 2, hanya rentang tanggal yang bisa diubah oleh pengawas.</p>
                </div>
            </div>
        </section>
    </x-ui.sidebar>
</div>

@endsection
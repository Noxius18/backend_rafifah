@extends('layouts.app')

@section('content')
<x-ui.sidebar>
    <section class="space-y-4">
        <div class="rounded-2xl border border-emerald-200 bg-white/90 p-6 shadow-sm shadow-emerald-200">
            <div class="flex items-center gap-4">
                <a href="{{ route('panitia.index') }}" class="btn btn-sm btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-semibold text-emerald-900">Edit Panitia</h1>
                    <p class="mt-2 text-sm text-slate-600">Update data panitia {{ $panitia->nama_lengkap }}</p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="rounded-2xl border border-emerald-100 bg-white/90 p-6 shadow-sm">
            <form action="{{ route('panitia.update', $panitia->id_panitia) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <!-- ID Panitia (Read Only) -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">ID Panitia</span>
                    </label>
                    <input 
                        type="text" 
                        value="{{ $panitia->id_panitia }}"
                        class="input input-bordered bg-base-200"
                        disabled
                    />
                    <label class="label">
                        <span class="label-text-alt text-base-content/50">ID tidak dapat diubah</span>
                    </label>
                </div>

                <!-- Nama Lengkap -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Nama Lengkap</span>
                        <span class="label-text-alt text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="nama_lengkap"
                        maxlength="30"
                        placeholder="Masukkan nama lengkap"
                        value="{{ old('nama_lengkap', $panitia->nama_lengkap) }}"
                        class="input input-bordered @error('nama_lengkap') input-error @enderror"
                        required
                    />
                    @error('nama_lengkap')
                        <label class="label">
                            <span class="label-text-alt text-red-500">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <!-- Username -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Username</span>
                        <span class="label-text-alt text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="username"
                        maxlength="10"
                        placeholder="Masukkan username"
                        value="{{ old('username', $panitia->username) }}"
                        class="input input-bordered @error('username') input-error @enderror"
                        required
                    />
                    @error('username')
                        <label class="label">
                            <span class="label-text-alt text-red-500">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <!-- No HP -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">No. HP</span>
                        <span class="label-text-alt text-red-500">*</span>
                    </label>
                    <input 
                        type="tel" 
                        name="no_hp"
                        maxlength="13"
                        placeholder="Contoh: 081234567890"
                        value="{{ old('no_hp', $panitia->no_hp) }}"
                        class="input input-bordered @error('no_hp') input-error @enderror"
                        required
                    />
                    @error('no_hp')
                        <label class="label">
                            <span class="label-text-alt text-red-500">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <!-- Jabatan -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Jabatan</span>
                        <span class="label-text-alt text-red-500">*</span>
                    </label>
                    <select 
                        name="jabatan"
                        class="select select-bordered @error('jabatan') select-error @enderror"
                        required
                    >
                        <option value="">-- Pilih Jabatan --</option>
                        <option value="Pengawas" @selected(old('jabatan', $panitia->jabatan) === 'Pengawas')>Pengawas</option>
                        <option value="Panitia" @selected(old('jabatan', $panitia->jabatan) === 'Panitia')>Panitia</option>
                        <option value="Penguji" @selected(old('jabatan', $panitia->jabatan) === 'Penguji')>Penguji</option>
                    </select>
                    @error('jabatan')
                        <label class="label">
                            <span class="label-text-alt text-red-500">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="divider"></div>

                <!-- Buttons -->
                <div class="flex gap-3">
                    <button type="submit" class="btn btn-success">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Update
                    </button>
                    <a href="{{ route('panitia.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="button" class="btn btn-outline btn-error ml-auto" onclick="openDeleteModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Hapus
                    </button>
                </div>

            </form>
        </div>
    </section>
</x-ui.sidebar>

{{-- Delete Confirmation Modal --}}
<x-ui.modal id="deleteModal" size="sm">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-slate-800">Konfirmasi Hapus</h3>
                <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan</p>
            </div>
        </div>
    </x-slot>

    <x-slot name="body">
        <p class="text-sm text-slate-600">
            Apakah Anda yakin ingin menghapus panitia <strong class="text-slate-800">{{ $panitia->nama_lengkap }}</strong>?
        </p>
    </x-slot>

    <x-slot name="footer">
        <button class="btn btn-ghost btn-sm" onclick="deleteModal.close()">Batal</button>
        <button class="btn btn-error btn-sm" onclick="document.getElementById('deleteForm').submit()">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Hapus
        </button>
    </x-slot>
</x-ui.modal>

{{-- Hidden Delete Form --}}
<form id="deleteForm" action="{{ route('panitia.destroy', $panitia->id_panitia) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
    function openDeleteModal() {
        deleteModal.showModal();
    }
</script>
@endsection

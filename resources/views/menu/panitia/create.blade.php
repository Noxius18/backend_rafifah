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
                    <h1 class="text-2xl font-semibold text-emerald-900">Tambah Panitia Baru</h1>
                    <p class="mt-2 text-sm text-slate-600">Isi form di bawah untuk menambah panitia baru.</p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="rounded-2xl border border-emerald-100 bg-white/90 p-6 shadow-sm">
            <form action="{{ route('panitia.store') }}" method="POST" class="space-y-4">
                @csrf

<!-- Jabatan -->
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
                        value="{{ old('nama_lengkap') }}"
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
                        value="{{ old('username') }}"
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
                        value="{{ old('no_hp') }}"
                        class="input input-bordered @error('no_hp') input-error @enderror"
                        required
                    />
                    @error('no_hp')
                        <label class="label">
                            <span class="label-text-alt text-red-500">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Password</span>
                        <span class="label-text-alt text-red-500">*</span>
                    </label>
                    <input 
                        type="password" 
                        name="password"
                        placeholder="Minimal 6 karakter"
                        class="input input-bordered @error('password') input-error @enderror"
                        required
                    />
                    @error('password')
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
                        <option value="Pengawas" @selected(old('jabatan') === 'Pengawas')>Pengawas</option>
                        <option value="Panitia" @selected(old('jabatan') === 'Panitia')>Panitia</option>
                        <option value="Penguji" @selected(old('jabatan') === 'Penguji')>Penguji</option>
                    </select>
                    @error('jabatan')
                        <label class="label">
                            <span class="label-text-alt text-red-500">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <!-- Buttons -->
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="btn btn-success">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Simpan
                    </button>
                    <a href="{{ route('panitia.index') }}" class="btn btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </section>
</x-ui.sidebar>
@endsection

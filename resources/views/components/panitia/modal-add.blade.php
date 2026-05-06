<x-ui.modal id="addModal" size="md">
    <x-slot name="header">
        <h3 class="text-lg font-semibold text-slate-800">Tambah Panitia Baru</h3>
    </x-slot>

    <x-slot name="body">
        <form id="addForm" class="space-y-3">
            @csrf

            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">Nama Lengkap <span class="text-red-500">*</span></span>
                </label>
                <input type="text" name="nama_lengkap" maxlength="30"
                    placeholder="Masukkan nama lengkap"
                    class="input input-bordered input-sm" required />
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.nama_lengkap ?? ''"></span>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">Username <span class="text-red-500">*</span></span>
                </label>
                <input type="text" name="username" maxlength="10"
                    placeholder="Masukkan username"
                    class="input input-bordered input-sm" required />
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.username ?? ''"></span>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">No. HP <span class="text-red-500">*</span></span>
                </label>
                <input type="tel" name="no_hp" maxlength="13"
                    placeholder="Contoh: 081234567890"
                    class="input input-bordered input-sm" required />
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.no_hp ?? ''"></span>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">Password <span class="text-red-500">*</span></span>
                </label>
                <input type="password" name="password"
                    placeholder="Minimal 6 karakter"
                    class="input input-bordered input-sm" required />
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.password ?? ''"></span>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-sm">Jabatan <span class="text-red-500">*</span></span>
                </label>
                <select name="jabatan" class="select select-bordered select-sm" required>
                    <option value="">-- Pilih Jabatan --</option>
                    <option value="Pengawas">Pengawas</option>
                    <option value="Panitia">Panitia</option>
                    <option value="Penguji">Penguji</option>
                </select>
                <span class="text-xs text-red-500 mt-1" x-text="formErrors.jabatan ?? ''"></span>
            </div>
        </form>
    </x-slot>

    <x-slot name="footer">
        <button class="btn btn-ghost btn-sm" @click="closeAddModal()">Batal</button>
        <button class="btn btn-success btn-sm" @click="submitAddForm()" :disabled="isSubmitting">
            <span x-show="!isSubmitting">Simpan</span>
            <span x-show="isSubmitting" class="loading loading-spinner loading-sm"></span>
        </button>
    </x-slot>
</x-ui.modal>
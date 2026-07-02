<x-ui.modal-form id="editModal" title="Edit Mahasantri">
    <x-slot name="body">
        @if($errors->any())
            <x-ui.alert type="error" alert="Periksa kembali data mahasantri.">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif
        <form id="editModal-form" action="{{ route('mahasantri.update', $m->id_mahasantri) }}" method="POST" class="space-y-3">
            @csrf
            @method('PUT')
            <div class="form-control">
                <label class="label"><span class="label-text text-sm font-semibold">KD Mahasantri</span></label>
                <input type="text" value="{{ $m->id_mahasantri }}" class="input input-bordered input-sm bg-base-200" disabled />
                <label class="label"><span class="label-text-alt text-base-content/50">KD tidak dapat diubah</span></label>
            </div>
            <x-ui.form-input name="nama_lengkap" label="Nama Lengkap" placeholder="Masukkan nama lengkap" maxlength="35" required value="{{ old('nama_lengkap', $m->nama_lengkap) }}" />
            <x-ui.form-input name="email" label="Email" placeholder="Masukkan email" maxlength="100" type="email" value="{{ old('email', $m->email) }}" />
            <x-ui.form-input name="tempat_lahir" label="Tempat Lahir" placeholder="Masukkan tempat lahir" maxlength="50" value="{{ old('tempat_lahir', $m->tempat_lahir) }}" />
            <x-ui.form-input name="tanggal_lahir" label="Tanggal Lahir" type="date" value="{{ old('tanggal_lahir', $m->tanggal_lahir ? (is_string($m->tanggal_lahir) ? $m->tanggal_lahir : $m->tanggal_lahir->format('Y-m-d')) : '') }}" />
            <x-ui.form-input name="nik" label="NIK" placeholder="16 digit NIK" maxlength="16" value="{{ old('nik', $m->nik) }}" />
            <x-ui.form-input name="nisn" label="NISN" placeholder="10 digit NISN" maxlength="10" value="{{ old('nisn', $m->nisn) }}" />
            <x-ui.form-input name="alamat" label="Alamat Tempat Tinggal" placeholder="Masukkan alamat" maxlength="255" value="{{ old('alamat', $m->alamat) }}" />
            <x-ui.form-select name="status" label="Status" :options="$statusOptions" :selected="old('status', $m->status)" />
        </form>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-ghost btn-sm" x-on:click="document.getElementById('editModal').close()">Batal</button>
        <button type="submit" form="editModal-form" class="btn btn-success btn-sm">Perbarui</button>
    </x-slot>
</x-ui.modal-form>

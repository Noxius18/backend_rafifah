@extends('layouts.app')

@section('content')

@php
    $aspekList = ['Tajwid', 'Tahsin', 'Kelancaran', 'Wawancara'];

    // ── HTML renderers untuk kolom tabel ───────────────────────────────────
    $columns = [
        ['label' => 'ID',        'field' => 'id_jadwal', 'html' => 'id_html'],
        ['label' => 'Periode Seleksi',  'field' => 'periode',  'html' => 'nama_html'],
        ['label' => 'Tanggal',   'field' => 'tanggal',   'html' => 'tgl_html'],
        ['label' => 'Penguji',   'field' => 'penguji_str','html' => 'penguji_html', 'class' => 'hidden md:table-cell'],
        ['label' => 'PIC',       'field' => 'pic',        'html' => 'pic_html',    'class' => 'hidden md:table-cell'],
        ['label' => 'Link Zoom', 'field' => 'link_zoom',  'html' => 'link_html',    'class' => 'hidden lg:table-cell'],
        ['label' => 'Aksi',      'field' => 'id_jadwal',  'html' => 'aksi_html',    'class' => 'text-right'],
    ];

    $rows = $jadwals->map(fn($j) => [
        'id_jadwal'   => $j->id_jadwal,
        'periode'     => $j->periode,
        'keterangan'  => $j->keterangan,
        'tanggal'     => $j->tanggal,
        'link_zoom'   => $j->link_zoom ?? '-',
        'penguji_str' => $j->pengujiList->map(fn($p) => $p->panitia->nama_lengkap . ' (' . $p->aspek . ')')->implode(', '),

        'id_html'   => "<code class='rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500'>{$j->id_jadwal}</code>",
        'nama_html' => "<span class='font-medium text-slate-700'>" . e($j->periode) . "</span>",
        'tgl_html'  => "<span class='text-xs text-slate-500'>" . \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') . "</span>",
        'penguji_html' => $j->pengujiList->isEmpty()
            ? "<span class='text-slate-400 text-xs'>Belum ditentukan</span>"
            : "<div class='space-y-0.5'>" . $j->pengujiList->map(fn($p) =>
                "<span class='inline-block rounded-md bg-indigo-50 px-1.5 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200'>" . e($p->panitia->nama_lengkap) . " <span class='text-indigo-400'>(" . e($p->aspek) . ")</span></span>"
              )->implode(' ') . "</div>",
        'link_html' => $j->link_zoom
            ? "<a href='" . e($j->link_zoom) . "' target='_blank' class='inline-flex items-center justify-center rounded-md p-2 text-slate-400 transition hover:bg-blue-50 hover:text-blue-600'
                   title='Buka Zoom'>
                   <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9A2.25 2.25 0 0013.5 5.25h-9A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75z'/></svg>
               </a>"
            : "<span class='text-slate-400 text-xs'>-</span>",
        'pic_html' => $j->picPanitia
            ? "<span class='inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200'>
                <svg xmlns='http://www.w3.org/2000/svg' class='h-3.5 w-3.5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'/></svg>
                " . e($j->picPanitia->nama_lengkap) . "
              </span>"
            : "<span class='text-slate-400 text-xs'>-</span>",
        'aksi_html' => "<div class='flex items-center justify-end gap-0.5'>
                            <a href='" . route('jadwal-tes.nilai', $j->id_jadwal) . "'
                                class='inline-flex items-center justify-center rounded-md p-2 text-slate-400 transition hover:bg-emerald-50 hover:text-emerald-600'
                                title='Input Nilai'>
                                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'/></svg>
                            </a>
                            " . (auth()->user()->jabatan === 'Panitia' ? "
                            <button type='button'
                                onclick=\"openEditModal({
                                    id:           '{$j->id_jadwal}',
                                    nama:         '" . e($j->periode) . "',
                                    keterangan:   '" . e($j->keterangan) . "',
                                    tanggal:      '{$j->tanggal}',
                                    link_zoom:    '" . e($j->link_zoom ?? '') . "',
                                    penguji_tajwid:     '" . e($j->pengujiList->where('aspek','Tajwid')->first()?->id_panitia ?? '') . "',
                                    penguji_tahsin:     '" . e($j->pengujiList->where('aspek','Tahsin')->first()?->id_panitia ?? '') . "',
                                    penguji_kelancaran: '" . e($j->pengujiList->where('aspek','Kelancaran')->first()?->id_panitia ?? '') . "',
                                    penguji_wawancara:  '" . e($j->pengujiList->where('aspek','Wawancara')->first()?->id_panitia ?? '') . "'
                                })\"
                                class='inline-flex items-center justify-center rounded-md p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700'
                                title='Edit'>
                                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10'/></svg>
                            </button>
                            <button type='button'
                                onclick=\"openConfirmModal('/jadwal-tes/{$j->id_jadwal}', '" . e($j->periode) . "')\"
                                class='inline-flex items-center justify-center rounded-md p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600'
                                title='Hapus'>
                                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0'/></svg>
                            </button>
                            " : '') . "
                        </div>",

        'search' => strtolower("{$j->id_jadwal} {$j->periode} {$j->keterangan}"),
    ])->toArray();
@endphp

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
    @if(session('error'))   showToast('{{ session('error') }}', 'error') @endif
">

    <x-ui.toast />

    <x-ui.sidebar>
        <section class="space-y-6 px-1 py-2">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-1 h-7 w-1 rounded-full bg-emerald-500"></div>
                    <div>
                        <h1 class="text-xl font-semibold text-slate-800">Jadwal Tes</h1>
                        <p class="text-sm text-slate-400">Kelola jadwal tes seleksi mahasantri.</p>
                    </div>
                </div>
                @if(auth()->user()->jabatan === 'Panitia')
                <button type="button" onclick="addModal.showModal()"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95">
                    <x-heroicon-s-plus class="h-4 w-4" />
                    Tambah Jadwal
                </button>
                @endif
            </div>

            {{-- Table --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <x-ui.data-table
                    :rows="$rows"
                    :columns="$columns"
                    :total="$jadwals->total()"
                    empty-message="Belum ada jadwal tes"
                    empty-sub="Mulai dengan menambahkan jadwal tes pertama."
                    add-label="Tambah Jadwal"
                />
                <x-ui.pagination :paginator="$jadwals" alwaysShow="true" />
            </div>

        </section>
    </x-ui.sidebar>

    {{-- ── Modal: Tambah ──────────────────────────────────────────────── --}}
    <x-ui.modal-form id="addModal" title="Tambah Jadwal Tes Baru" size="lg">
        <x-slot name="body">
            <form id="addModal-form" action="{{ route('jadwal-tes.store') }}" method="POST" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.form-input name="periode" label="Periode Seleksi" placeholder="Contoh: Gelombang 1" maxlength="20" required />
                    <x-ui.form-input name="keterangan" label="Keterangan" placeholder="Contoh: Tes Seleksi" maxlength="50" required />
                </div>
                <x-ui.form-input name="tanggal" label="Tanggal Tes" type="date" required />
                <x-ui.form-input name="link_zoom" label="Link Zoom" placeholder="https://zoom.us/j/..." />

                {{-- Penguji per aspek --}}
                <div class="border-t border-slate-100 pt-3">
                    <p class="mb-2 text-sm font-semibold text-slate-700">Tentukan Penguji per Aspek</p>
                    <p class="mb-3 text-xs text-slate-400">Pilih panitia yang akan menjadi penguji untuk setiap aspek penilaian.</p>

                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($aspekList as $aspek)
                            @php
                                $field = 'penguji_' . strtolower($aspek);
                            @endphp
                            <x-ui.form-select
                                name="{{ $field }}"
                                label="Penguji {{ $aspek }}"
                                :options="$panitias->pluck('nama_lengkap', 'id_panitia')->toArray()"
                                placeholder="-- Pilih Panitia --"
                            />
                        @endforeach
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="addModal.close()">Batal</button>
            <button type="submit" form="addModal-form" class="btn btn-success btn-sm">Simpan</button>
        </x-slot>
    </x-ui.modal-form>

    {{-- ── Modal: Edit ────────────────────────────────────────────────── --}}
    <x-ui.modal-form id="editModal" title="Edit Jadwal Tes" size="lg">
        <x-slot name="body">
            <div class="max-h-[65vh] overflow-y-auto -mr-2 pr-2">
            <form id="editModal-form" action="" method="POST" class="space-y-3">
                @csrf
                @method('PUT')

                {{-- ID readonly --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold text-sm">ID Jadwal</span>
                    </label>
                    <input type="text" id="editModal-id-display" class="input input-bordered input-sm bg-base-200" disabled />
                    <label class="label">
                        <span class="label-text-alt text-base-content/50">ID tidak dapat diubah</span>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <x-ui.form-input name="periode" label="Periode Seleksi" placeholder="Contoh: Gelombang 1" maxlength="20" required />
                    <x-ui.form-input name="keterangan" label="Keterangan" placeholder="Contoh: Tes Seleksi" maxlength="50" required />
                </div>
                <x-ui.form-input name="tanggal" label="Tanggal Tes" type="date" required />
                <x-ui.form-input name="link_zoom" label="Link Zoom" placeholder="https://zoom.us/j/..." />

                {{-- Penguji per aspek --}}
                <div class="border-t border-slate-100 pt-3">
                    <p class="mb-2 text-sm font-semibold text-slate-700">Tentukan Penguji per Aspek</p>
                    <p class="mb-3 text-xs text-slate-400">Pilih panitia yang akan menjadi penguji untuk setiap aspek penilaian.</p>

                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($aspekList as $aspek)
                            @php
                                $field = 'penguji_' . strtolower($aspek);
                            @endphp
                            <x-ui.form-select
                                name="{{ $field }}"
                                label="Penguji {{ $aspek }}"
                                :options="$panitias->pluck('nama_lengkap', 'id_panitia')->toArray()"
                                placeholder="-- Pilih Panitia --"
                            />
                        @endforeach
                    </div>
                </div>
            </form>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm text-slate-500 hover:text-slate-700 hover:bg-slate-100" onclick="editModal.close()">Batal</button>
            <button type="submit" form="editModal-form" class="btn btn-sm bg-emerald-600 text-white hover:bg-emerald-700 border-none gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                </svg>
                Perbarui
            </button>
        </x-slot>
    </x-ui.modal-form>

    {{-- ── Modal: Hapus ───────────────────────────────────────────────── --}}
    <x-ui.modal-confirm
        id="deleteModal"
        title="Konfirmasi Hapus"
        body-text="Apakah Anda yakin ingin menghapus jadwal tes"
        confirm-label="Hapus"
    />

</div>

{{-- JS minimal --}}
<script>
    function openEditModal({ id, nama, keterangan, tanggal, link_zoom, penguji_tajwid, penguji_tahsin, penguji_kelancaran, penguji_wawancara }) {
        document.getElementById('editModal-form').action = `/jadwal-tes/${id}`;

        document.getElementById('editModal-id-display').value = id;
        document.querySelector('#editModal-form [name="periode"]').value = nama;
        document.querySelector('#editModal-form [name="keterangan"]').value = keterangan;
        document.querySelector('#editModal-form [name="tanggal"]').value = tanggal;
        document.querySelector('#editModal-form [name="link_zoom"]').value = link_zoom;

        // Set penguji per aspek
        document.querySelector('#editModal-form [name="penguji_tajwid"]').value = penguji_tajwid;
        document.querySelector('#editModal-form [name="penguji_tahsin"]').value = penguji_tahsin;
        document.querySelector('#editModal-form [name="penguji_kelancaran"]').value = penguji_kelancaran;
        document.querySelector('#editModal-form [name="penguji_wawancara"]').value = penguji_wawancara;

        editModal.showModal();
    }
</script>

@endsection
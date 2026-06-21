@extends('layouts.app')

@php
    $isPanitia = auth()->user()->jabatan === 'Panitia';
    $isKetuaPanitia = auth()->user()->jabatan === 'Ketua Panitia';

    $formatLinkZoom = function($link) {
        $link = trim($link);
        if (!preg_match('~^(https?://)~i', $link)) {
            $link = 'https://' . $link;
        }
        return $link;
    };
@endphp

@section('content')

<div x-data="{
    toast: { show: false, message: '', type: 'success', timer: null },
    showToast(message, type = 'success') {
        if (this.toast.timer) clearTimeout(this.toast.timer);
        Object.assign(this.toast, { message, type, show: true, timer: setTimeout(() => this.toast.show = false, 4000) });
    },
    openEditModal(jadwal) {
        this.editId = jadwal.id;
        this.editJam = jadwal.jam || '';
        this.editLinkZoom = jadwal.link_zoom || '';
        editModal.showModal();
    },
    editId: null, editJam: '', editLinkZoom: '',
    async submitEdit() {
        try {
            const res = await fetch('/seleksi/' + this.editId, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ jam: this.editJam, link_zoom: this.editLinkZoom }),
            });
            const data = await res.json();
            if (res.ok) { this.showToast('Jadwal berhasil diperbarui'); setTimeout(() => location.reload(), 1000); }
            else { this.showToast(data.message || 'Gagal', 'error'); }
        } catch(e) { this.showToast('Gagal', 'error'); }
    }
}"
x-init="@if(session('success')) showToast('{{ session('success') }}') @endif @if(session('error')) showToast('{{ session('error') }}', 'error') @endif">

    @include('menu.jadwal-tes.partials.header')
    @include('menu.jadwal-tes.partials.stat-cards')
    @include('menu.jadwal-tes.partials.filter-bar')
    @include('menu.jadwal-tes.partials.bulk-actions')

    <x-ui.sidebar>
        <section class="space-y-6 px-1 py-2">
            {{-- Tabel Jadwal Seleksi --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-700">Daftar Jadwal Seleksi</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">{{ $jadwals->total() }}</span>
                    </div>
                    <input type="text" id="searchInput" placeholder="Cari jadwal..." class="w-48 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-600 placeholder:text-slate-400 focus:border-indigo-300 focus:ring-1 focus:ring-indigo-200 outline-none" onkeyup="filterTable(this.value)" />
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="jadwalTable">
                        <thead class="border-b border-slate-100 text-left">
                            <tr class="text-xs font-medium text-slate-400">
                                <th class="px-4 py-3">Kode</th>
                                <th class="px-4 py-3">Nama Mahasantri</th>
                                <th class="px-4 py-3">Gelombang</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Jam</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 hidden lg:table-cell">Penguji</th>
                                <th class="px-4 py-3 hidden lg:table-cell">Link Zoom</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($jadwals as $j)
                            @php
                                // Ambil data jadwalPenguji untuk ditampilkan
                                $pengujiList = $j->jadwalPenguji->map(function($jp) {
                                    return ($jp->panitia?->nama_lengkap ?? '—') . ' (' . $jp->aspek_penguji . ')';
                                })->implode(', ');
                                $pengujiHtml = $pengujiList ?: '<span class="text-slate-400 text-xs">—</span>';

                                $statusJadwal = $j->status_jadwal ?? 'Menunggu';
                                $statusJadwalHtml = match($statusJadwal) {
                                    'Disetujui' => '<span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Disetujui</span>',
                                    'Dibatalkan' => '<span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200">Dibatalkan</span>',
                                    'Rescheduled' => '<span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">Rescheduled</span>',
                                    'Aktif' => '<span class="rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-200">Aktif</span>',
                                    'Revisi' => '<span class="rounded-md bg-orange-50 px-2 py-0.5 text-xs font-medium text-orange-700 ring-1 ring-orange-200">Perlu Revisi</span>',
                                    default => '<span class="rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-500 ring-1 ring-slate-200">Menunggu</span>',
                                };

                                $gelombangLabel = $j->mahasantri ? \App\Models\User::extractGelombangNama($j->mahasantri->id_mahasantri) : '-';
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-mono text-xs font-medium text-slate-500">{{ $j->id_jadwal }}</span>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $j->mahasantri?->nama_lengkap ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ $gelombangLabel }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    @if($j->jam)
                                        <span class="rounded-md bg-slate-50 px-2 py-0.5 text-xs font-mono font-medium text-slate-600 ring-1 ring-slate-200">{{ \Carbon\Carbon::parse($j->jam)->format('H:i') }}</span>
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{!! $statusJadwalHtml !!}</td>
                                <td class="px-4 py-3 hidden lg:table-cell text-xs text-slate-500">{!! $pengujiHtml !!}</td>
                                <td class="px-4 py-3 hidden lg:table-cell">
                                    @if($j->link_zoom)
                                        <a href="{{ $formatLinkZoom($j->link_zoom) }}" target="_blank" class="inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-blue-600 hover:bg-blue-50" title="Buka Zoom">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9A2.25 2.25 0 0013.5 5.25h-9A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75z"/></svg>
                                        </a>
                                    @else
                                        <span class="text-black/50 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-0.5">
                                        @if($isPanitia && $j->status_jadwal !== 'Disetujui')
                                            <button type="button" @click="openEditModal({ id: '{{ $j->id_jadwal }}', jam: '{{ $j->jam ? \Carbon\Carbon::parse($j->jam)->format('H:i') : '' }}', link_zoom: '{{ e($j->link_zoom ?? '') }}' })" class="inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-indigo-600 hover:bg-indigo-50" title="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                            </button>
                                        @endif
                                        <a href="{{ route('seleksi.nilai', $j->id_jadwal) }}" class="inline-flex items-center justify-center rounded-md p-2 text-emerald-600 transition hover:bg-emerald-50" title="Input Nilai">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="px-4 py-12 text-center text-sm text-slate-400">Belum ada jadwal seleksi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($jadwals->hasPages())
                <div class="border-t border-slate-100 px-4 py-3">
                    {{ $jadwals->links() }}
                </div>
                @endif
            </div>
        </section>
    </x-ui.sidebar>

    {{-- Modal: Edit Jadwal --}}
    <x-ui.modal id="editModal" size="sm">
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100">
                    <x-heroicon-s-pencil-square class="h-5 w-5 text-indigo-600" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Edit Jadwal</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Ubah jam dan link zoom</p>
                </div>
            </div>
        </x-slot>
        <x-slot name="body">
            <div class="space-y-4 py-2">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Jam</span></label>
                    <input type="time" x-model="editJam" class="input input-bordered input-sm w-full" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Link Zoom</span></label>
                    <input type="url" x-model="editLinkZoom" placeholder="https://zoom.us/j/..." class="input input-bordered input-sm w-full" />
                </div>
                <p class="text-xs text-amber-600">*Setelah diedit, status akan kembali ke "Menunggu" untuk review ulang Ketua Panitia.</p>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-ghost btn-sm text-slate-500 hover:text-slate-700 hover:bg-slate-100" onclick="editModal.close()">Batal</button>
            <button type="button" @click="submitEdit()" class="btn btn-primary btn-sm gap-1.5">
                <span>Simpan</span>
            </button>
        </x-slot>
    </x-ui.modal>
</div>

<script>
function filterTable(value) {
    const filter = value.toLowerCase();
    const rows = document.querySelectorAll('#jadwalTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}
</script>

@endsection
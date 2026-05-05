@extends('layouts.app')

@section('content')
<x-ui.sidebar>
    <section class="space-y-6 px-1 py-2">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div class="flex items-start gap-3">
                <div class="mt-1 h-7 w-1 rounded-full bg-emerald-500"></div>
                <div>
                    <h1 class="text-xl font-semibold text-slate-800">Daftar Panitia</h1>
                    <p class="text-sm text-slate-400">Kelola data panitia, pengawas, dan penguji.</p>
                </div>
            </div>
            <a href="{{ route('panitia.create') }}"
                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Tambah
            </a>
        </div>

        {{-- Alert --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show"
                x-transition:leave="transition duration-200 ease-in"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-700">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ session('success') }}
                </div>
                <button @click="show = false" class="text-emerald-400 hover:text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        {{-- Table Card --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">

            @php
                // Closure HTML untuk tiap kolom
                $jabatanBadge = fn($val) => match($val) {
                    'Penguji'  => "<span class='rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200'>Penguji</span>",
                    'Panitia'  => "<span class='rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200'>Panitia</span>",
                    'Pengawas' => "<span class='rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-sky-200'>Pengawas</span>",
                    default    => "<span class='text-slate-400'>" . e($val) . "</span>",
                };

                $namaHtml = fn($val) =>
                    "<div class='flex items-center gap-2.5'>
                        <div class='flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-bold text-emerald-700'>"
                            . strtoupper(substr($val, 0, 1)) .
                        "</div>
                        <span class='font-medium text-slate-700'>" . e($val) . "</span>
                    </div>";

                $idHtml       = fn($val) => "<code class='rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500'>" . e($val) . "</code>";
                $usernameHtml = fn($val) => "<span class='font-mono text-xs text-slate-500'>" . e($val) . "</span>";
                $aksiHtml     = fn($val) =>
                    "<div class='flex items-center justify-end gap-1'>
                        <a href='/panitia/" . e($val) . "/edit'
                            class='rounded-md px-2.5 py-1 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700'>Edit</a>
                        <span class='text-slate-200'>|</span>
                        <button onclick=\"deletePanitia('" . e($val) . "')\"
                            class='rounded-md px-2.5 py-1 text-xs font-medium text-slate-500 transition hover:bg-rose-50 hover:text-rose-600'>Hapus</button>
                    </div>";

                // $rows berisi dua jenis field per kolom:
                // - nilai plain  → untuk search & sort (mis. 'jabatan' => 'Penguji')
                // - nilai html   → untuk ditampilkan di tabel (mis. 'jabatan_html')
                $rows = $panitias->map(fn($p) => [
                    // Plain values — dipakai untuk search & sort
                    'id_panitia'   => $p->id_panitia,
                    'nama_lengkap' => $p->nama_lengkap,
                    'username'     => $p->username,
                    'no_hp'        => $p->no_hp,
                    'jabatan'      => $p->jabatan,

                    // HTML values — ditampilkan via x-html di template
                    'id_html'       => $idHtml($p->id_panitia),
                    'nama_html'     => $namaHtml($p->nama_lengkap),
                    'username_html' => $usernameHtml($p->username),
                    'jabatan_html'  => $jabatanBadge($p->jabatan),
                    'aksi_html'     => $aksiHtml($p->id_panitia),

                    // Search index — satu string gabungan semua field untuk filter cepat
                    'search' => strtolower(implode(' ', [$p->id_panitia, $p->nama_lengkap, $p->username, $p->no_hp, $p->jabatan])),
                ])->toArray();
            @endphp

            @if($panitias->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-600">Belum ada data panitia</p>
                    <p class="mt-1 text-xs text-slate-400">Mulai dengan menambahkan panitia pertama.</p>
                    <a href="{{ route('panitia.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Tambah Panitia
                    </a>
                </div>

            @else
                <div x-data="panitiaTable()">

                    {{-- Toolbar --}}
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="relative w-full sm:max-w-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text"
                                placeholder="Cari panitia..."
                                x-model="searchTerm"
                                @input="filterAndSort()"
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-sm text-slate-700 placeholder-slate-400 outline-none transition focus:border-emerald-400 focus:bg-white focus:ring-2 focus:ring-emerald-100"
                            />
                        </div>
                        <span class="text-xs text-slate-400">
                            <span class="font-medium text-slate-600" x-text="filteredRows.length"></span>
                            dari {{ $panitias->count() }} data
                        </span>
                    </div>

                    {{-- Table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-slate-100 text-left">
                                <tr class="text-xs font-medium text-slate-400">
                                    {{-- Setiap th bisa diklik untuk sort, pakai field plain value --}}
                                    @foreach([
                                        ['label' => 'ID',       'field' => 'id_panitia'],
                                        ['label' => 'Nama',     'field' => 'nama_lengkap'],
                                        ['label' => 'Username', 'field' => 'username'],
                                        ['label' => 'No. HP',   'field' => 'no_hp'],
                                        ['label' => 'Jabatan',  'field' => 'jabatan'],
                                    ] as $col)
                                        <th @click="toggleSort('{{ $col['field'] }}')"
                                            class="cursor-pointer select-none px-4 py-3 hover:text-slate-600">
                                            <div class="flex items-center gap-1">
                                                {{ $col['label'] }}
                                                <span x-show="sortColumn === '{{ $col['field'] }}'"
                                                      x-text="sortDirection === 'asc' ? '↑' : '↓'"
                                                      class="text-emerald-500"></span>
                                            </div>
                                        </th>
                                    @endforeach
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <template x-for="row in filteredRows" :key="row.id_panitia">
                                    <tr class="hover:bg-slate-50/70">
                                        {{-- Gunakan *_html untuk tampilan, bukan plain value --}}
                                        <td class="px-4 py-3"            x-html="row.id_html"></td>
                                        <td class="px-4 py-3"            x-html="row.nama_html"></td>
                                        <td class="px-4 py-3"            x-html="row.username_html"></td>
                                        <td class="px-4 py-3 text-slate-500" x-text="row.no_hp"></td>
                                        <td class="px-4 py-3"            x-html="row.jabatan_html"></td>
                                        <td class="px-4 py-3"            x-html="row.aksi_html"></td>
                                    </tr>
                                </template>
                                <tr x-show="filteredRows.length === 0" x-cloak>
                                    <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">
                                        Tidak ada hasil untuk
                                        "<span class="text-slate-600" x-text="searchTerm"></span>"
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer --}}
                    <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-400">
                        Menampilkan
                        <span class="font-medium text-slate-600" x-text="filteredRows.length"></span>
                        dari {{ $panitias->count() }} panitia
                    </div>

                </div>
            @endif
        </div>

    </section>
</x-ui.sidebar>

<script>
    function panitiaTable() {
        return {
            searchTerm: '',
            sortColumn: null,
            sortDirection: 'asc',
            rows: @json($rows),         // data asli, tidak pernah diubah
            filteredRows: @json($rows), // data yang ditampilkan (hasil filter + sort)

            // Filter berdasarkan 'search' index, lalu sort
            filterAndSort() {
                const term = this.searchTerm.trim().toLowerCase();

                this.filteredRows = term
                    ? this.rows.filter(row => row.search.includes(term))
                    : [...this.rows];

                if (this.sortColumn) this.applySort();
            },

            // Toggle arah sort atau ganti kolom
            toggleSort(column) {
                this.sortDirection = this.sortColumn === column
                    ? (this.sortDirection === 'asc' ? 'desc' : 'asc')
                    : 'asc';
                this.sortColumn = column;
                this.applySort();
            },

            // Sort filteredRows berdasarkan plain value (bukan html)
            applySort() {
                const dir = this.sortDirection === 'asc' ? 1 : -1;
                this.filteredRows.sort((a, b) => {
                    const aVal = a[this.sortColumn] ?? '';
                    const bVal = b[this.sortColumn] ?? '';
                    return typeof aVal === 'string'
                        ? aVal.localeCompare(bVal) * dir
                        : (aVal - bVal) * dir;
                });
            },
        };
    }

    function deletePanitia(id) {
        if (!confirm('Hapus panitia ini?')) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/panitia/${id}`;
        form.innerHTML = `
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
</script>
@endsection
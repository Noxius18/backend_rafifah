@props(['panitias', 'rows'])

@if ($panitias->isEmpty())
    {{-- Empty state --}}
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </div>
        <p class="text-sm font-medium text-slate-600">Belum ada data panitia</p>
        <p class="mt-1 text-xs text-slate-400">Mulai dengan menambahkan panitia pertama.</p>
        <a href="#" @click.prevent="openAddModal()"
            class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-emerald-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Tambah Panitia
        </a>
    </div>

@else
    <div x-data="panitiaTable()" x-init="rows = filteredRows = {{ Js::from($rows) }}">

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
                            <td class="px-4 py-3"                x-html="row.id_html"></td>
                            <td class="px-4 py-3"                x-html="row.nama_html"></td>
                            <td class="px-4 py-3"                x-html="row.username_html"></td>
                            <td class="px-4 py-3 text-slate-500" x-text="row.no_hp"></td>
                            <td class="px-4 py-3"                x-html="row.jabatan_html"></td>
                            <td class="px-4 py-3"                x-html="row.aksi_html"></td>
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
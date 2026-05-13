{{--
    <x-ui.data-table
        :rows="$rows"
        :columns="$columns"
        :total="$collection->count()"
        empty-message="Belum ada data"
        empty-sub="Mulai dengan menambahkan data pertama."
        add-label="Tambah"
        add-action="openAddModal()"
    />

    $columns = [
        ['label' => 'Nama',    'field' => 'nama',      'html' => 'nama_html'],  // html field → x-html
        ['label' => 'No. HP',  'field' => 'no_hp'],                             // tanpa html  → x-text
        ['label' => 'Aksi',    'field' => 'id',        'html' => 'aksi_html', 'class' => 'text-right'],
    ]

    Tiap row wajib punya key 'search' → string gabungan semua field untuk filter.
--}}

@props([
    'rows'         => [],
    'columns'      => [],
    'total'        => 0,
    'emptyMessage' => 'Belum ada data',
    'emptySub'     => 'Mulai dengan menambahkan data baru.',
    'addLabel'     => 'Tambah',
    'addAction'    => 'openAddModal()',
])

@if (count($rows) === 0)
    {{-- Empty state --}}
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </div>
        <p class="text-sm font-medium text-slate-600">{{ $emptyMessage }}</p>
        <p class="mt-1 text-xs text-slate-400">{{ $emptySub }}</p>
    </div>

@else
    <div x-data="{
        searchTerm: '',
        sortColumn: null,
        sortDirection: 'asc',
        rows: {{ Js::from($rows) }},
        filteredRows: {{ Js::from($rows) }},

        filterAndSort() {
            const term = this.searchTerm.trim().toLowerCase();
            this.filteredRows = term
                ? this.rows.filter(r => r.search.includes(term))
                : [...this.rows];
            if (this.sortColumn) this.applySort();
        },
        toggleSort(col) {
            this.sortDirection = this.sortColumn === col
                ? (this.sortDirection === 'asc' ? 'desc' : 'asc')
                : 'asc';
            this.sortColumn = col;
            this.applySort();
        },
        applySort() {
            const dir = this.sortDirection === 'asc' ? 1 : -1;
            this.filteredRows.sort((a, b) => {
                const av = a[this.sortColumn] ?? '', bv = b[this.sortColumn] ?? '';
                return typeof av === 'string' ? av.localeCompare(bv) * dir : (av - bv) * dir;
            });
        },
    }">

        {{-- Toolbar --}}
        <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text"
                    placeholder="Cari..."
                    x-model="searchTerm"
                    @input="filterAndSort()"
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-sm text-slate-700 placeholder-slate-400 outline-none transition focus:border-emerald-400 focus:bg-white focus:ring-2 focus:ring-emerald-100"
                />
            </div>
            <span class="text-xs text-slate-400">
                <span class="font-medium text-slate-600" x-text="filteredRows.length"></span>
                dari {{ $total }} data
            </span>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100 text-left">
                    <tr class="text-xs font-medium text-slate-400">
                        @foreach ($columns as $col)
                            <th @click="toggleSort('{{ $col['field'] }}')"
                                class="cursor-pointer select-none px-4 py-3 hover:text-slate-600 {{ $col['class'] ?? '' }}">
                                <div class="flex items-center gap-1">
                                    {{ $col['label'] }}
                                    <span x-show="sortColumn === '{{ $col['field'] }}'"
                                          x-text="sortDirection === 'asc' ? '↑' : '↓'"
                                          class="text-emerald-500"></span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <template x-for="(row, i) in filteredRows" :key="i">
                        <tr class="hover:bg-slate-50/70">
                            @foreach ($columns as $col)
                                @if (isset($col['html']))
                                    <td class="px-4 py-3 {{ $col['class'] ?? '' }}" x-html="row['{{ $col['html'] }}']"></td>
                                @else
                                    <td class="px-4 py-3 {{ $col['class'] ?? '' }}" x-text="row['{{ $col['field'] }}']"></td>
                                @endif
                            @endforeach
                        </tr>
                    </template>
                    <tr x-show="filteredRows.length === 0" x-cloak>
                        <td colspan="{{ count($columns) }}" class="px-4 py-12 text-center text-sm text-slate-400">
                            Tidak ada hasil untuk "<span class="text-slate-600" x-text="searchTerm"></span>"
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-400">
            Menampilkan <span class="font-medium text-slate-600" x-text="filteredRows.length"></span>
            dari {{ $total }} data
        </div>

    </div>
@endif
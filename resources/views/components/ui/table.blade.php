@props([
    'headers' => [],
    'rows' => [],
    'striped' => true,
    'hover' => true,
    'compact' => false,
    'searchable' => false,
    'sortable' => false,
])

<div 
  class="overflow-x-auto"
  @if($searchable || $sortable)
    x-data="tableData()"
    @if($searchable) @keyup="filterRows()" @endif
  @endif
>
  <!-- Search Bar (optional) -->
  @if($searchable)
    <div class="mb-4">
      <input 
        type="text" 
        placeholder="Cari..." 
        x-model="searchTerm"
        class="input input-bordered w-full max-w-xs"
      />
    </div>
  @endif

  <table class="table {{ $striped ? 'table-zebra' : '' }} {{ $hover ? 'table-hover' : '' }} {{ $compact ? 'table-compact' : '' }} w-full">
    <!-- Table Head -->
    <thead>
      <tr class="bg-base-200">
        @foreach($headers as $index => $header)
          <th 
            @if($sortable)
              @click="toggleSort({{ $index }})"
              class="cursor-pointer hover:bg-base-300"
            @endif
          >
            <div class="flex items-center gap-2">
              <span>
                @if(is_array($header))
                  {{ $header['label'] ?? $header['key'] ?? '' }}
                @else
                  {{ $header }}
                @endif
              </span>
              @if($sortable)
                <span 
                  x-show="sortColumn === {{ $index }}"
                  x-text="sortDirection === 'asc' ? '▲' : '▼'"
                  class="text-xs"
                ></span>
              @endif
            </div>
          </th>
        @endforeach
      </tr>
    </thead>

    <!-- Table Body -->
    <tbody>
      @forelse($rows as $row)
        <tr 
          @if($searchable)
            x-show="isRowVisible({{ $loop->index }})"
          @endif
          class="border-b border-base-200"
        >
          @foreach($headers as $header)
            <td>
              @if(is_array($header))
                @php
                  $key = $header['key'] ?? '';
                  $format = $header['format'] ?? null;
                @endphp
                @if($format)
                  {!! $format($row[$key] ?? '') !!}
                @else
                  {{ $row[$key] ?? '-' }}
                @endif
              @else
                {{ $row[$header] ?? '-' }}
              @endif
            </td>
          @endforeach
        </tr>
      @empty
        <tr>
          <td colspan="{{ count($headers) }}" class="text-center py-8 text-base-content/50">
            Tidak ada data
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<!-- Alpine.js Component Logic -->
@if($searchable || $sortable)
  <script>
    function tableData() {
      return {
        searchTerm: '',
        sortColumn: null,
        sortDirection: 'asc',
        originalRows: @json($rows),
        filteredRows: @json($rows),

        filterRows() {
          if (!this.searchTerm) {
            this.filteredRows = this.originalRows;
            return;
          }

          const term = this.searchTerm.toLowerCase();
          this.filteredRows = this.originalRows.filter(row => {
            return Object.values(row).some(value => 
              String(value).toLowerCase().includes(term)
            );
          });
        },

        toggleSort(columnIndex) {
          if (this.sortColumn === columnIndex) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
          } else {
            this.sortColumn = columnIndex;
            this.sortDirection = 'asc';
          }
          this.sortRows();
        },

        sortRows() {
          if (this.sortColumn === null) return;

          const headers = @json($headers);
          const key = Array.isArray(headers[this.sortColumn]) 
            ? headers[this.sortColumn]['key'] 
            : headers[this.sortColumn];

          this.filteredRows.sort((a, b) => {
            const aVal = a[key];
            const bVal = b[key];

            if (typeof aVal === 'string') {
              return this.sortDirection === 'asc' 
                ? aVal.localeCompare(bVal) 
                : bVal.localeCompare(aVal);
            }

            return this.sortDirection === 'asc' 
              ? aVal - bVal 
              : bVal - aVal;
          });
        },

        isRowVisible(index) {
          if (!this.searchTerm) return true;
          return this.filteredRows.includes(this.originalRows[index]);
        }
      }
    }
  </script>
@endif
// Satu store tunggal — panitiaManager & panitiaTable digabung
document.addEventListener('alpine:init', () => {
    Alpine.store('panitia', {
        rows: [],
        filteredRows: [],
        searchTerm: '',
        sortColumn: null,
        sortDirection: 'asc',

        init(rows) {
            this.rows = rows;
            this.filteredRows = [...rows];
        },

        filterAndSort() {
            const term = this.searchTerm.trim().toLowerCase();
            this.filteredRows = term
                ? this.rows.filter(r => r.search.includes(term))
                : [...this.rows];
            if (this.sortColumn) this.applySort();
        },

        toggleSort(column) {
            this.sortDirection = this.sortColumn === column
                ? (this.sortDirection === 'asc' ? 'desc' : 'asc')
                : 'asc';
            this.sortColumn = column;
            this.applySort();
        },

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
    });
});
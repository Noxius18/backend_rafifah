<x-ui.modal id="deleteModal" size="sm">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-slate-800">Konfirmasi Hapus</h3>
                <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan</p>
            </div>
        </div>
    </x-slot>

    <x-slot name="body">
        <p class="text-sm text-slate-600">
            Apakah Anda yakin ingin menghapus data panitia
            <strong class="text-slate-800" id="deleteName"></strong>?
        </p>
    </x-slot>

    <x-slot name="footer">
        <button class="btn btn-ghost btn-sm" onclick="deleteModal.close()">Batal</button>
        <button class="btn btn-error btn-sm"
            onclick="document.dispatchEvent(new CustomEvent('confirmDelete'))">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Hapus
        </button>
    </x-slot>
</x-ui.modal>
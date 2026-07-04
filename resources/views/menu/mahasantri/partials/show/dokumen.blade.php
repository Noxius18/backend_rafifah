@php
    /** @var \App\Models\User $m */
@endphp

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
        <h2 class="text-sm font-semibold text-slate-700">Dokumen Pendaftaran</h2>
    </div>
    <div class="p-5">
        @if($m->berkas && $m->berkas->count() > 0)
            <div class="overflow-hidden rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tipe Dokumen</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Unduh</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Verifikasi</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tanggal Upload</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($m->berkas as $doc)
                            @php($previewIndex = collect($previewDocs)->search(fn($previewDoc) => $previewDoc['id'] === $doc->id_berkas))
                            <tr class="transition hover:bg-slate-50" data-berkas-id="{{ $doc->id_berkas }}">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-s-document-text class="h-4 w-4 text-slate-400" />
                                        {{ $doc->tipe_berkas }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @switch($doc->riwayatUnduhan?->download_status)
                                        @case('success')
                                            <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Berhasil</span>
                                            @break
                                        @case('processing')
                                            <span class="rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-sky-200">Mengunduh...</span>
                                            @break
                                        @case('failed')
                                            <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200">Gagal</span>
                                            @break
                                        @default
                                            <span class="rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-500 ring-1 ring-slate-200">Menunggu</span>
                                    @endswitch
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if(strtolower($doc->status_verifikasi) === 'disetujui')
                                        <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">✅ Disetujui</span>
                                    @elseif(strtolower($doc->status_verifikasi) === 'ditolak')
                                        <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200">❌ Ditolak</span>
                                        @if($doc->catatan_revisi)
                                            <div class="text-[10px] text-rose-600 mt-1 italic font-medium max-w-[180px] whitespace-normal">Catatan: {{ $doc->catatan_revisi }}</div>
                                        @endif
                                    @else
                                        <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">⏳ Menunggu</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{{ $doc->tanggal_upload ? (is_string($doc->tanggal_upload) ? $doc->tanggal_upload : $doc->tanggal_upload->translatedFormat('d F Y')) : '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- BUTTON REVISI DI SINI SUDAH DIHAPUS BERSIH SESUAI REQUEST --}}
                                        @if($doc->riwayatUnduhan?->download_status === 'success' && $doc->file_path)
                                            <a href="{{ route('berkas.download', $doc->id_berkas) }}"
                                                class="inline-flex items-center justify-center rounded-md p-2 text-emerald-600 transition hover:bg-emerald-50"
                                                title="Unduh">
                                                <x-heroicon-s-arrow-down-tray class="h-4 w-4" />
                                            </a>
                                        @endif
                                        @if($doc->riwayatUnduhan?->download_status === 'failed')
                                            <button type="button" x-on:click="retryDownload('{{ $doc->id_berkas }}')"
                                                class="inline-flex items-center justify-center rounded-md p-2 text-amber-600 transition hover:bg-amber-50"
                                                title="Ulangi">
                                                <x-heroicon-s-arrow-path class="h-4 w-4" />
                                            </button>
                                        @endif
                                        @if($doc->riwayatUnduhan?->download_status === 'success' && $doc->file_path)
                                            <button type="button" x-on:click="openPreview({{ $previewIndex !== false ? $previewIndex : 0 }}, @js($m->nik), @js($m->nisn))"
                                                class="inline-flex items-center justify-center rounded-md p-2 text-indigo-600 transition hover:bg-indigo-50"
                                                title="Lihat">
                                                <x-heroicon-s-eye class="h-4 w-4" />
                                            </button>
                                        @else
                                            <span class="inline-flex items-center justify-center rounded-md p-2 text-slate-400" title="Preview tidak tersedia">
                                                <x-heroicon-s-eye-slash class="h-4 w-4" />
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <x-heroicon-s-document class="h-10 w-10 text-slate-300" />
                <p class="mt-2 text-sm font-medium text-slate-500">Belum ada dokumen</p>
                <p class="text-xs text-slate-400">Dokumen belum diunggah untuk mahasantri ini.</p>
            </div>
        @endif
    </div>
</div>

{{-- MODAL CUSTOM 1: KONFIRMASI PERSETUJUAN BERKAS --}}
<dialog id="approveBerkasModal" class="modal">
    <div class="modal-box max-w-sm rounded-xl border border-slate-100 shadow-xl bg-white">
        <div class="flex items-center gap-3 text-emerald-600">
            <div class="p-2 bg-emerald-50 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
            </div>
            <h3 class="font-bold text-lg text-slate-800">Setujui Dokumen</h3>
        </div>
        <p class="text-xs text-slate-500 mt-2 leading-relaxed">Apakah Anda yakin seluruh isi berkas pendaftaran <span id="labelApproveTipeBerkas" class="font-semibold text-slate-700"></span> ini sudah sah dan sesuai?</p>
        <input type="hidden" id="submitApproveIdBerkas" />
        <div class="modal-action flex justify-end gap-2 mt-5">
            <button type="button" class="btn btn-ghost btn-sm text-xs rounded-lg" onclick="document.getElementById('approveBerkasModal').close()">Batal</button>
            <button type="button" class="btn btn-success btn-sm text-xs text-white rounded-lg px-4" onclick="executeApproveBerkas()">Ya, Setujui</button>
        </div>
    </div>
</dialog>

{{-- MODAL CUSTOM 2: INPUT CATATAN REVISI --}}
<dialog id="rejectBerkasModal" class="modal">
    <div class="modal-box max-w-sm rounded-xl border border-slate-100 shadow-xl bg-white">
        <div class="flex items-center gap-3 text-rose-600">
            <div class="p-2 bg-rose-50 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
            <h3 class="font-bold text-lg text-slate-800">Tolak Dokumen</h3>
        </div>
        <p class="text-xs text-slate-500 mt-2">Berikan alasan penolakan berkas: <span id="labelTipeBerkas" class="font-semibold text-slate-700"></span></p>
        <input type="hidden" id="submitRejectIdBerkas" />
        <div class="form-control mt-3">
            <label class="label"><span class="label-text text-xs font-semibold text-slate-600">Catatan Revisi Ke Mahasantri</span></label>
            <textarea id="inputCatatanRevisi" rows="3" class="textarea textarea-bordered text-sm border-slate-200 rounded-xl resize-none outline-none focus:border-rose-400" placeholder="Contoh: Foto berkas buram, mohon unggah ulang dokumen asli..."></textarea>
        </div>
        <div class="modal-action flex justify-end gap-2 mt-4">
            <button type="button" class="btn btn-ghost btn-sm text-xs rounded-lg" onclick="document.getElementById('rejectBerkasModal').close()">Batal</button>
            <button type="button" class="btn btn-error btn-sm text-xs text-white rounded-lg px-4" onclick="submitRejectBerkas()">Konfirmasi Tolak</button>
        </div>
    </div>
</dialog>

{{-- CONTAINER FLOATING TOAST MODERN --}}
<div id="customToastLayout" class="toast toast-top toast-end z-[9999] p-4 hidden">
    <div id="toastAlertBox" class="alert shadow-lg border-0 rounded-xl py-3 px-4 text-xs font-semibold text-white flex items-center gap-2">
        <span id="toastIconSlot"></span>
        <span id="toastMessageSlot"></span>
    </div>
</div>

<script>
    function triggerCustomToast(message, type = 'success') {
        const layout = document.getElementById('customToastLayout');
        const box = document.getElementById('toastAlertBox');
        const iconSlot = document.getElementById('toastIconSlot');
        const messageSlot = document.getElementById('toastMessageSlot');
        if (!layout || !box) return;

        if (type === 'error') {
            box.className = "alert alert-error shadow-lg border-0 rounded-xl py-3 px-4 text-xs font-semibold text-white flex items-center gap-2 bg-rose-600";
            iconSlot.innerText = "❌";
        } else {
            box.className = "alert alert-success shadow-lg border-0 rounded-xl py-3 px-4 text-xs font-semibold text-white flex items-center gap-2 bg-emerald-600";
            iconSlot.innerText = "✨";
        }
        messageSlot.innerText = message;
        layout.classList.remove('hidden');
        setTimeout(() => { layout.classList.add('hidden'); }, 3500);
    }

    async function executeReviewApi(idBerkas, statusValue, catatan = null) {
        try {
            const targetUrl = window.location.origin + '/api/panitia/berkas/' + idBerkas + '/review';
            const response = await fetch(targetUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: statusValue, catatan_revisi: catatan })
            });
            const data = await response.json();
            if (response.ok && data.success) {
                triggerCustomToast(data.message || 'Status dokumen berhasil diperbarui!');
                setTimeout(() => { location.reload(); }, 1200);
            } else {
                const errorMsg = data.errors?.catatan_revisi ? data.errors.catatan_revisi[0] : (data.message || 'Gagal mengubah status berkas.');
                triggerCustomToast(errorMsg, 'error');
            }
        } catch (error) {
            console.error('Review berkas error:', error);
            triggerCustomToast('Terjadi kegagalan koneksi sistem.', 'error');
        }
    }

    function openApproveBerkasModal(idBerkas, tipeBerkas) {
        document.getElementById('submitApproveIdBerkas').value = idBerkas;
        document.getElementById('labelApproveTipeBerkas').innerText = tipeBerkas;
        document.getElementById('approveBerkasModal').showModal();
    }

     Kakakak
    function executeApproveBerkas() {
        const idBerkas = document.getElementById('submitApproveIdBerkas').value;
        document.getElementById('approveBerkasModal').close();
        executeReviewApi(idBerkas, 'disetujui');
    }

    function openRejectBerkasModal(idBerkas, tipeBerkas) {
        document.getElementById('submitRejectIdBerkas').value = idBerkas;
        document.getElementById('labelTipeBerkas').innerText = tipeBerkas;
        document.getElementById('inputCatatanRevisi').value = '';
        document.getElementById('rejectBerkasModal').showModal();
    }

    function submitRejectBerkas() {
        const idBerkas = document.getElementById('submitRejectIdBerkas').value;
        const catatan = document.getElementById('inputCatatanRevisi').value.trim();
        if (!catatan) {
            triggerCustomToast('Alasan catatan revisi wajib diisi jika berkas ditolak!', 'error');
            document.getElementById('inputCatatanRevisi').focus();
            return;
        }
        document.getElementById('rejectBerkasModal').close();
        executeReviewApi(idBerkas, 'ditolak', catatan);
    }
</script>
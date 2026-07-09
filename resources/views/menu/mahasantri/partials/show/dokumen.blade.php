@php
    /** @var \App\Models\User $m */
    $downloadStatusBadges = [
        'success' => 'rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200',
        'processing' => 'rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-sky-200',
        'failed' => 'rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200',
        'default' => 'rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-500 ring-1 ring-slate-200',
    ];
    $downloadStatusLabels = [
        'success' => 'Berhasil',
        'processing' => 'Mengunduh...',
        'failed' => 'Gagal',
        'default' => 'Menunggu',
    ];
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
                            @php
                                $previewIndex = collect($previewDocs)->search(fn($previewDoc) => $previewDoc['id'] === $doc->id_berkas);
                                $downloadStatus = $doc->riwayatUnduhan?->download_status ?? 'default';
                                $verificationStatus = strtolower($doc->status_verifikasi);
                                $canDownload = $downloadStatus === 'success' && $doc->file_path;
                                $canRetry = $downloadStatus === 'failed';
                                $canPreview = $canDownload;
                            @endphp
                            <tr class="transition hover:bg-slate-50" data-berkas-id="{{ $doc->id_berkas }}">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-s-document-text class="h-4 w-4 text-slate-400" />
                                        {{ $doc->tipe_berkas }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="{{ $downloadStatusBadges[$downloadStatus] ?? $downloadStatusBadges['default'] }}">
                                        {{ $downloadStatusLabels[$downloadStatus] ?? $downloadStatusLabels['default'] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if($previewIndex !== false)
                                        <span :class="statusBadgeClass(getDisplayStatus('{{ $doc->id_berkas }}', '{{ $verificationStatus }}'))"
                                            x-text="statusLabel(getDisplayStatus('{{ $doc->id_berkas }}', '{{ $verificationStatus }}'))"></span>
                                        <div x-show="isDraftDirtyById('{{ $doc->id_berkas }}')" class="mt-1 text-[10px] font-semibold text-sky-600">
                                            Perubahan belum disimpan
                                        </div>
                                        <div x-show="getDisplayStatus('{{ $doc->id_berkas }}', '{{ $verificationStatus }}') === 'ditolak' && getDisplayCatatan('{{ $doc->id_berkas }}', @js($doc->catatan_revisi ?? ''))"
                                            class="mt-1 max-w-[180px] whitespace-normal text-[10px] font-medium italic text-rose-600"
                                            x-text="`Catatan: ${getDisplayCatatan('{{ $doc->id_berkas }}', @js($doc->catatan_revisi ?? ''))}`"></div>
                                    @elseif($verificationStatus === 'disetujui')
                                        <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">✅ Disetujui</span>
                                    @elseif($verificationStatus === 'ditolak')
                                        <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200">❌ Ditolak</span>
                                        @if($doc->catatan_revisi)
                                            <div class="mt-1 max-w-[180px] whitespace-normal text-[10px] font-medium italic text-rose-600">Catatan: {{ $doc->catatan_revisi }}</div>
                                        @endif
                                    @else
                                        <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">⏳ Menunggu</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{{ $doc->tanggal_upload ? (is_string($doc->tanggal_upload) ? $doc->tanggal_upload : $doc->tanggal_upload->translatedFormat('d F Y')) : '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if($canDownload)
                                            <a href="{{ route('berkas.download', $doc->id_berkas) }}"
                                                class="inline-flex items-center justify-center rounded-md p-2 text-emerald-600 transition hover:bg-emerald-50"
                                                title="Unduh">
                                                <x-heroicon-s-arrow-down-tray class="h-4 w-4" />
                                            </a>
                                        @endif
                                        @if($canRetry)
                                            <button type="button" x-on:click="retryDownload('{{ $doc->id_berkas }}')"
                                                class="inline-flex items-center justify-center rounded-md p-2 text-amber-600 transition hover:bg-amber-50"
                                                title="Ulangi">
                                                <x-heroicon-s-arrow-path class="h-4 w-4" />
                                            </button>
                                        @endif
                                        @if($canPreview)
                                            <button type="button" x-on:click="openPreview({{ $previewIndex !== false ? $previewIndex : 0 }})"
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
        <p class="text-xs text-slate-500 mt-2 leading-relaxed">Apakah Anda yakin seluruh isi berkas pendaftaran <span class="font-semibold text-slate-700" x-text="approveTarget.title"></span> ini sudah sah dan sesuai?</p>
        <div class="modal-action flex justify-end gap-2 mt-5">
            <button type="button" class="btn btn-ghost btn-sm text-xs rounded-lg" x-on:click="document.getElementById('approveBerkasModal').close(); document.getElementById('previewModal').showModal()">Batal</button>
            <button type="button" class="btn btn-success btn-sm text-xs text-white rounded-lg px-4" x-on:click="confirmApproveDraft()">Ya, Setujui</button>
        </div>
    </div>
</dialog>

<dialog id="rejectBerkasModal" class="modal">
    <div class="modal-box max-w-sm rounded-xl border border-slate-100 shadow-xl bg-white">
        <div class="flex items-center gap-3 text-rose-600">
            <div class="p-2 bg-rose-50 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
            <h3 class="font-bold text-lg text-slate-800">Tolak Dokumen</h3>
        </div>
        <p class="text-xs text-slate-500 mt-2">Berikan alasan penolakan berkas: <span class="font-semibold text-slate-700" x-text="rejectTarget.title"></span></p>
        <div class="form-control mt-3">
            <label class="label"><span class="label-text text-xs font-semibold text-slate-600">Catatan Revisi Ke Mahasantri</span></label>
            <textarea id="inputCatatanRevisi" x-model="rejectDraftNote" rows="3" class="textarea textarea-bordered text-sm border-slate-200 rounded-xl resize-none outline-none focus:border-rose-400" placeholder="Contoh: Foto berkas buram, mohon unggah ulang dokumen asli..."></textarea>
        </div>
        <div class="modal-action flex justify-end gap-2 mt-4">
            <button type="button" class="btn btn-ghost btn-sm text-xs rounded-lg" x-on:click="document.getElementById('rejectBerkasModal').close(); document.getElementById('previewModal').showModal()">Batal</button>
            <button type="button" class="btn btn-error btn-sm text-xs text-white rounded-lg px-4" x-on:click="confirmRejectDraft()">Konfirmasi Tolak</button>
        </div>
    </div>
</dialog>

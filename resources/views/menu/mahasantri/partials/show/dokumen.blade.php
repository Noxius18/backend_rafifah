<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
        <h2 class="text-sm font-semibold text-slate-700">Dokumen</h2>
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
                                    @if($doc->status_verifikasi)
                                        <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Terverifikasi</span>
                                    @else
                                        <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">Belum Verifikasi</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{{ $doc->tanggal_upload ? (is_string($doc->tanggal_upload) ? $doc->tanggal_upload : $doc->tanggal_upload->translatedFormat('d F Y')) : '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
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

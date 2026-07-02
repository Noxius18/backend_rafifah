<dialog id="previewModal" class="modal" x-on:click="if ($event.target === $el) $el.close();">
    <div class="modal-box w-full max-w-5xl">
        <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
            <h3 class="text-base font-semibold text-slate-800" x-text="previewTitle"></h3>
            <button type="button" class="btn btn-ghost btn-sm btn-square" x-on:click="document.getElementById('previewModal').close()">
                <x-heroicon-s-x-mark class="h-5 w-5" />
            </button>
        </div>

        <div class="gap-4 lg:grid lg:grid-cols-3">
            <div class="flex flex-col lg:col-span-2">
                <template x-if="isImageDoc">
                    <div class="flex min-h-[40vh] w-full items-center justify-center rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <img :src="previewUrl" :alt="previewTitle" class="max-h-[45vh] max-w-full rounded object-contain shadow-sm" />
                    </div>
                </template>
                <template x-if="!isImageDoc">
                    <iframe :src="previewUrl" class="min-h-[40vh] w-full rounded-lg border border-slate-200" frameborder="0" allowfullscreen></iframe>
                </template>

                <template x-if="previewTotal > 1">
                    <div class="mt-3">
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <template x-for="(doc, idx) in previewDocs" :key="doc.id">
                                <button type="button" x-on:click="previewDocIndex = idx"
                                    class="relative flex min-w-[64px] flex-col items-center gap-1 rounded-lg border-2 p-1.5 transition hover:bg-slate-50"
                                    :class="idx === previewDocIndex ? 'border-emerald-500 bg-emerald-50' : (doc.isValid !== doc.localIsValid ? 'border-amber-400 bg-amber-50' : 'border-slate-200')">
                                    <template x-if="doc.isImage">
                                        <img :src="doc.url" class="h-10 w-10 rounded object-cover" />
                                    </template>
                                    <template x-if="!doc.isImage">
                                        <div class="flex h-10 w-10 items-center justify-center rounded bg-slate-100">
                                            <x-heroicon-s-document-text class="h-5 w-5 text-slate-400" />
                                        </div>
                                    </template>
                                    <span class="text-center text-[10px] font-medium leading-tight text-slate-600" x-text="doc.title"></span>
                                </button>
                            </template>
                        </div>
                        <div class="mt-2 flex items-center justify-center gap-3">
                            <button type="button" x-on:click="prevDoc()" :disabled="previewDocIndex === 0"
                                class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-30"
                                :class="previewDocIndex > 0 ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'text-slate-400'">
                                <x-heroicon-s-chevron-left class="h-3.5 w-3.5" />
                                Sebelumnya
                            </button>
                            <span class="min-w-[3rem] text-center text-xs font-medium text-slate-500" x-text="`${previewDocIndex + 1} / ${previewTotal}`"></span>
                            <button type="button" x-on:click="nextDoc()" :disabled="previewDocIndex === previewTotal - 1"
                                class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-30"
                                :class="previewDocIndex < previewTotal - 1 ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'text-slate-400'">
                                Selanjutnya
                                <x-heroicon-s-chevron-right class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            @if(auth()->user()->jabatan === 'Panitia')
                <div class="flex min-h-[40vh] flex-col justify-between rounded-lg border border-slate-200 bg-white p-4">
                    <div>
                        <div class="mb-3 flex items-center gap-2">
                            <x-heroicon-s-clipboard-document-check class="h-4 w-4 text-emerald-600" />
                            <span class="text-sm font-semibold text-slate-700">Verifikasi Dokumen</span>
                        </div>
                        <div class="mb-4 flex items-center gap-1.5 rounded-md border border-slate-100 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                            <x-heroicon-s-calendar-days class="h-3.5 w-3.5" />
                            <span>Upload:</span>
                            <span class="font-medium text-slate-700" x-text="previewDoc.uploadDate || '-'"></span>
                        </div>
                        <div class="mb-4 rounded-lg border p-3" :class="currentLocalIsValid ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider" :class="currentLocalIsValid ? 'text-emerald-700' : 'text-amber-700'" x-text="currentLocalIsValid ? 'Terverifikasi' : 'Belum Verifikasi'"></span>
                                <button type="button" x-on:click="togglePreviewStatus()"
                                    :class="currentLocalIsValid ? 'bg-emerald-500' : 'bg-gray-300'"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                                    <span :class="currentLocalIsValid ? 'translate-x-5' : 'translate-x-0'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="mb-2 flex items-center gap-2">
                                <x-heroicon-s-identification class="h-3.5 w-3.5 text-slate-400" />
                                <span class="text-xs font-semibold text-slate-600">Data Pribadi</span>
                                <span class="text-[10px] text-slate-400">Cocokkan dengan dokumen</span>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-500">NIK</label>
                                <div class="relative">
                                    <input type="text" x-model="previewNik" maxlength="16" placeholder="16 digit NIK"
                                        x-on:input="previewNik = previewNik.replace(/\D/g, '').slice(0, 16)"
                                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 pr-12 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-400 focus:outline-none focus:ring-1 focus:ring-emerald-400">
                                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-medium text-slate-400" x-text="previewNik.length + '/16'"></span>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-500">NISN</label>
                                <div class="relative">
                                    <input type="text" x-model="previewNisn" maxlength="10" placeholder="10 digit NISN"
                                        x-on:input="previewNisn = previewNisn.replace(/\D/g, '').slice(0, 10)"
                                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 pr-12 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-400 focus:outline-none focus:ring-1 focus:ring-emerald-400">
                                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-medium text-slate-400" x-text="previewNisn.length + '/10'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-end border-t border-slate-100 pt-3">
                        <button type="button" x-on:click="saveBerkasStatus()" :disabled="saving"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

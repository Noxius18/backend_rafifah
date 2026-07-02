<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
        <h2 class="text-sm font-semibold text-slate-700">Data Orangtua / Wali</h2>
    </div>
    <div class="p-5">
        @if($m->orangtuas && $m->orangtuas->count() > 0)
            <div class="space-y-3">
                @foreach($m->orangtuas as $ort)
                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                        <div class="mb-3">
                            <span class="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">{{ $ort->tipe_hubungan }}</span>
                        </div>
                        <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">Nama Lengkap</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $ort->nama_lengkap }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">Pekerjaan</dt>
                                <dd class="mt-0.5 text-sm text-slate-700">{{ $ort->pekerjaan ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">No. WhatsApp</dt>
                                <dd class="mt-0.5">
                                    @if($ort->no_wa)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $ort->no_wa) }}" target="_blank"
                                           class="inline-flex items-center gap-1 text-sm font-medium text-emerald-600 hover:text-emerald-700">
                                            <x-heroicon-s-phone class="h-3.5 w-3.5" />
                                            {{ $ort->no_wa }}
                                        </a>
                                    @else
                                        <span class="text-sm text-slate-400">-</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">Alamat</dt>
                                <dd class="mt-0.5 text-sm text-slate-700">{{ $ort->alamat ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <x-heroicon-s-user-group class="h-10 w-10 text-slate-300" />
                <p class="mt-2 text-sm font-medium text-slate-500">Belum ada data orangtua</p>
                <p class="text-xs text-slate-400">Data orangtua/wali belum ditambahkan untuk mahasantri ini.</p>
            </div>
        @endif
    </div>
</div>

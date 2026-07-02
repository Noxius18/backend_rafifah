<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
        <h2 class="text-sm font-semibold text-slate-700">Data Pribadi</h2>
    </div>
    <div class="p-5">
        <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">KD Mahasantri</dt>
                <dd class="mt-1"><code class="rounded bg-black/[0.05] px-1.5 py-0.5 text-xs font-medium text-black">{{ $m->id_mahasantri }}</code></dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">Nama Lengkap</dt>
                <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $m->nama_lengkap }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">Email</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $m->email ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">Tempat Lahir</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $m->tempat_lahir ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">Tanggal Lahir</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $m->tanggal_lahir ? (is_string($m->tanggal_lahir) ? $m->tanggal_lahir : $m->tanggal_lahir->translatedFormat('d F Y')) : '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">NIK</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $m->nik ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">NISN</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $m->nisn ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">Alamat Tempat Tinggal</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $m->alamat ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">Status</dt>
                <dd class="mt-1">
                    @switch($m->status)
                        @case('Pendaftar Baru')
                            <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">Pendaftar Baru</span>
                            @break
                        @case('Terverifikasi')
                            <span class="rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-sky-200">Terverifikasi</span>
                            @break
                        @case('Lulus')
                            <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Lulus</span>
                            @break
                        @case('Tidak Lulus')
                            <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200">Tidak Lulus</span>
                            @break
                        @default
                            <span class="text-sm text-slate-700">{{ $m->status }}</span>
                    @endswitch
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">Tanggal Daftar</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $m->tanggal_daftar ? (is_string($m->tanggal_daftar) ? $m->tanggal_daftar : $m->tanggal_daftar->translatedFormat('d F Y')) : '-' }}</dd>
            </div>
        </dl>
    </div>
</div>

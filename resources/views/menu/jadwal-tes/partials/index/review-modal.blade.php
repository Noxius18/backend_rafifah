@php
    $hasPendingReview = $totalMenunggu > 0;
@endphp

<x-ui.modal id="reviewModal" size="xl">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100">
                <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
            </div>
            <div>
                @foreach ([
                    'info' => 'Review & Tindak Lanjut Jadwal',
                    'reject_form' => 'Ajukan Perubahan Jadwal',
                ] as $step => $title)
                    <h3 class="text-lg font-semibold text-slate-800" x-show="reviewStep === '{{ $step }}'">{{ $title }}</h3>
                @endforeach
                <p class="mt-0.5 text-xs text-slate-500">Ketua Panitia — review data jadwal sebelum memutuskan</p>
            </div>
        </div>
    </x-slot>
    <x-slot name="body">
        <div x-show="reviewStep === 'info'">
            @if($hasPendingReview)
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
                    <strong class="font-bold">{{ $totalMenunggu }}</strong> jadwal menunggu persetujuan dari <strong class="font-bold">{{ count($jadwalsByTanggal) }}</strong> tanggal berbeda.
                </div>

                <div class="max-h-[50vh] space-y-3 overflow-y-auto -mr-2 pr-2">
                    @foreach($jadwalsByTanggal as $group)
                        <div class="overflow-hidden rounded-lg border border-slate-200">
                            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-2">
                                <span class="text-sm font-semibold text-slate-700">{{ \Carbon\Carbon::parse($group['tanggal'])->format('d/m/Y') }}</span>
                                <span class="text-xs text-slate-500">{{ $group['total'] }} jadwal</span>
                            </div>
                            <div class="space-y-1.5 px-4 py-3">
                                @foreach($group['penguji'] as $aspek => $nama)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-slate-600">{{ $aspek }}</span>
                                        <span class="font-medium text-slate-800">{{ $nama }}</span>
                                    </div>
                                @endforeach
                                <div class="mt-1.5 flex items-center justify-between border-t border-slate-100 pt-1.5 text-sm">
                                    <span class="text-slate-500">Penanggung Jawab</span>
                                    <span class="font-medium text-slate-800">{{ $group['penanggung_jawab'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center">
                    <svg class="mx-auto mb-3 h-12 w-12 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium text-slate-700">✅ Semua jadwal sudah diproses</p>
                    <p class="mt-1 text-xs text-slate-400">Tidak ada jadwal yang menunggu persetujuan.</p>
                </div>
            @endif
        </div>

        <div x-show="reviewStep === 'reject_form'">
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
                Ajukan perubahan untuk <strong class="font-bold">SEMUA</strong> jadwal yang masih <strong>Menunggu</strong> atau <strong>Revisi</strong>.
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text text-sm font-semibold">Alasan Perubahan</span></label>
                <textarea x-model="rejectNote" class="textarea textarea-bordered" rows="4" placeholder="Tuliskan alasan perubahan jadwal..." required></textarea>
            </div>
            <p class="mt-2 text-xs text-slate-400" x-show="rejectNote.length > 0 && rejectNote.length < 5">Alasan minimal 5 karakter.</p>
        </div>

        <form id="approveSemuaForm" action="{{ route('seleksi.approve-all') }}" method="POST" class="hidden">@csrf</form>
        <form id="rejectSemuaForm" action="{{ route('seleksi.reject-all') }}" method="POST" class="hidden">
            @csrf
            <input id="rejectNoteInput" type="hidden" name="catatan_perubahan" value="" />
        </form>
    </x-slot>
    <x-slot name="footer">
        <template x-if="reviewStep === 'info'">
            <div class="flex w-full items-center justify-between gap-2">
                <button type="button" class="btn btn-ghost btn-sm" x-on:click="document.getElementById('reviewModal').close()">Tutup</button>
                @if($hasPendingReview)
                    <div class="flex items-center gap-2">
                        <button type="button" x-on:click="showRejectForm()" class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-white px-3.5 py-2 text-sm font-medium text-amber-600 transition hover:bg-amber-50 active:scale-95 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
                            Ajukan Perubahan
                        </button>
                        <button type="button" x-on:click="submitApprove()" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-95 shadow-sm">
                            <x-heroicon-s-check-circle class="h-4 w-4" />
                            Setujui Semua
                        </button>
                    </div>
                @endif
            </div>
        </template>
        <template x-if="reviewStep === 'reject_form'">
            <div class="flex w-full items-center justify-between gap-2">
                <button type="button" x-on:click="backToInfo()" class="btn btn-ghost btn-sm">Kembali</button>
                <button type="button" x-on:click="submitReject()" :disabled="!rejectNote.trim() || rejectNote.trim().length < 5"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-amber-700 active:scale-95 shadow-sm disabled:cursor-not-allowed disabled:opacity-50">
                    Kirim Perubahan
                </button>
            </div>
        </template>
    </x-slot>
</x-ui.modal>

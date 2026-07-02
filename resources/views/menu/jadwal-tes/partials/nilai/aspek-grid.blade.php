<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
    @foreach($nilaiAspekCards as $aspek)
        <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:shadow-sm {{ $aspek['canEditScore'] || $aspek['canEditNote'] ? '' : 'opacity-70' }} {{ $isKetuaPanitia && $isPertimbangan && $aspek['isNilaiBelow71'] ? 'ring-2 ring-amber-300 bg-amber-50/30' : '' }}">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">{{ $aspek['label'] }}</h3>
                @if($isKetuaPanitia && $isPertimbangan && $aspek['isNilaiBelow71'])
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700">Perlu Perbaikan</span>
                @elseif($aspek['canEditScore'])
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700">Dapat Diedit</span>
                @else
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-400">Read-only</span>
                @endif
            </div>

            <input type="number" min="0" max="100"
                x-model="formData.{{ $aspek['field'] }}"
                x-on:input="clampNilai($event, '{{ $aspek['field'] }}')"
                class="w-full rounded-lg border-2 px-3 py-2.5 text-center text-lg font-bold transition outline-none {{ $aspek['canEditScore'] ? 'border-slate-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100' : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-500' }}"
                {{ $aspek['canEditScore'] ? '' : 'disabled' }}
                placeholder="0-100" />

            <div class="flex items-center justify-between text-[11px] text-slate-400">
                <span>Penguji:</span>
                <span class="font-medium text-slate-500">{{ $aspek['pengujiNama'] }}</span>
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-medium text-slate-400">Catatan Panitia</label>
                <textarea x-model="formData.{{ $aspek['catatanField'] }}" rows="2"
                    class="w-full resize-none rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs transition outline-none {{ $aspek['canEditNote'] ? 'focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100' : 'cursor-not-allowed bg-slate-50 text-slate-500' }}"
                    placeholder="Catatan..."
                    {{ $aspek['canEditNote'] ? '' : 'disabled' }}></textarea>
            </div>
        </div>
    @endforeach
</div>

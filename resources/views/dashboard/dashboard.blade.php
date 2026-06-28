@extends('layouts.app')

@section('content')
  <x-ui.sidebar>
    <div x-data="dashboard()" x-init="init()" class="space-y-6">
      
      {{-- HEADER DASHBOARD --}}
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard Sistem</h1>
          <p class="text-sm text-slate-500">Selamat datang kembali! Berikut pantauan data registrasi mahasantri baru.</p>
        </div>
      </div>

      {{-- TOP METRICS: TOTAL & AKTIVITAS --}}
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Basis Data Mahasantri</p>
            <h3 class="text-3xl font-bold text-slate-800 mt-1" x-text="stats.total_mahasantri">0</h3>
            <p class="text-xs text-emerald-600 font-medium mt-1 flex items-center gap-0.5">
              🚀 +<span x-text="stats.recent_mahasantri_count">0</span> Pendaftar baru minggu ini
            </p>
          </div>
          <div class="rounded-xl bg-emerald-50 p-3 text-emerald-600">
            <x-heroicon-s-academic-cap class="h-6 w-6" />
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">BERKAS BELUM DI VERIFIKASI</p>
            <h3 class="text-3xl font-bold text-blue-600 mt-1" x-text="stats.baru_count">0</h3>
            <p class="text-xs text-slate-400 mt-1">Status: Pendaftar Baru</p>
          </div>
          <div class="rounded-xl bg-blue-50 p-3 text-blue-600">
            <x-heroicon-s-document-text class="h-6 w-6" />
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Panitia</p>
            <h3 class="text-3xl font-bold text-slate-800 mt-1" x-text="stats.total_panitia">0</h3>
            <p class="text-xs text-slate-400 mt-1">Panitia & Ketua Panitia</p>
          </div>
          <div class="rounded-xl bg-slate-100 p-3 text-slate-600">
            <x-heroicon-s-users class="h-6 w-6" />
          </div>
        </div>
      </div>

      {{-- SELEKSI GRID CORE STATUS (4 KOLOM BERSIH DAN STRUKTURAL) --}}
      <div>
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 mb-3">Progression & Status Hasil Seleksi</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          
          <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2 text-emerald-600">
              <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
              <span class="text-xs font-bold uppercase tracking-wide">Lulus</span>
            </div>
            <h4 class="text-2xl font-bold text-slate-800 mt-2" x-text="stats.lulus_count">0</h4>
            <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
              <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" :style="`width: ${stats.total_mahasantri ? (stats.lulus_count / stats.total_mahasantri) * 100 : 0}%`"></div>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2 text-amber-600">
              <span class="h-2 w-2 rounded-full bg-amber-500"></span>
              <span class="text-xs font-bold uppercase tracking-wide">Pertimbangan</span>
            </div>
            <h4 class="text-2xl font-bold text-slate-800 mt-2" x-text="stats.pertimbangan_count">0</h4>
            <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
              <div class="bg-amber-500 h-1.5 rounded-full transition-all duration-500" :style="`width: ${stats.total_mahasantri ? (stats.pertimbangan_count / stats.total_mahasantri) * 100 : 0}%`"></div>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2 text-rose-600">
              <span class="h-2 w-2 rounded-full bg-rose-500"></span>
              <span class="text-xs font-bold uppercase tracking-wide">Tidak Lulus</span>
            </div>
            <h4 class="text-2xl font-bold text-slate-800 mt-2" x-text="stats.tidak_lulus_count">0</h4>
            <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
              <div class="bg-rose-500 h-1.5 rounded-full transition-all duration-500" :style="`width: ${stats.total_mahasantri ? (stats.tidak_lulus_count / stats.total_mahasantri) * 100 : 0}%`"></div>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2 text-purple-600">
              <span class="h-2 w-2 rounded-full bg-purple-500"></span>
              <span class="text-xs font-bold uppercase tracking-wide">Terverifikasi (Siap Tes)</span>
            </div>
            <h4 class="text-2xl font-bold text-slate-800 mt-2" x-text="stats.terverif_count">0</h4>
            <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
              <div class="bg-purple-500 h-1.5 rounded-full transition-all duration-500" :style="`width: ${stats.total_mahasantri ? (stats.terverif_count / stats.total_mahasantri) * 100 : 0}%`"></div>
            </div>
          </div>

        </div>
      </div>

      {{-- JADWAL PERSETUJUAN KETUA & INFO GELOMBANG --}}
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        
        {{-- BLOK JADWAL MENUNGGU APPROVAL (Kiri / Lebar 1/3) --}}
        @if(auth()->user()->jabatan === 'Ketua Panitia')
        <div class="card bg-white border border-amber-200 shadow-sm rounded-xl p-4 lg:col-span-1 flex flex-col justify-between">
          <div>
            <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wide flex items-center gap-1.5 mb-3">
              <span>⏳</span> Persetujuan Jadwal Ujian
            </h3>
            <div class="space-y-2 overflow-y-auto max-h-[180px] pr-1">
              <template x-for="item in stats.jadwal_menunggu_persetujuan || []" :key="item.tanggal">
                <div class="flex items-center justify-between p-2.5 bg-amber-50 rounded-lg border border-amber-200 text-xs">
                  <span class="font-semibold text-amber-900" x-text="item.tanggal_label"></span>
                  <span class="rounded bg-amber-200/60 px-2 py-0.5 font-bold text-amber-800" x-text="item.jumlah + ' Mhs'"></span>
                </div>
              </template>
            </div>
            <p x-show="!stats.jadwal_menunggu_persetujuan || !stats.jadwal_menunggu_persetujuan.length" class="text-center text-xs text-slate-400 py-6 italic">
              Bersih! Tidak ada jadwal antre.
            </p>
          </div>
          <div class="border-t border-amber-100 pt-2 mt-2" x-show="stats.jadwal_menunggu_persetujuan && stats.jadwal_menunggu_persetujuan.length">
            <a href="{{ route('seleksi.index') }}" class="block text-center text-xs font-semibold text-amber-700 hover:underline">Buka Menu Peninjauan &rarr;</a>
          </div>
        </div>
        @endif

        {{-- BLOK INFO GELOMBANG (Kanan / Lebar Sisa Grid) --}}
        <div class="card bg-white border border-slate-200 shadow-sm rounded-xl p-4 @if(auth()->user()->jabatan === 'Ketua Panitia') lg:col-span-2 @else lg:col-span-3 @endif">
          <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-3">🗓️ Timeline Periode Gelombang</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <template x-for="item in stats.gelombang || []" :key="item.id">
              <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-1.5">
                  <span class="font-bold text-xs text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200" x-text="item.nama"></span>
                  <span class="text-[10px] text-slate-400" x-text="'ID: ' + item.id"></span>
                </div>
                <div class="space-y-1 text-xs text-slate-600">
                  <div class="flex items-center justify-between">
                    <span>Mulai Pendaftaran:</span>
                    <strong class="text-slate-700" x-text="formatTanggal(item.start_date)"></strong>
                  </div>
                  <div class="flex items-center justify-between">
                    <span>Penutupan Gelombang:</span>
                    <strong class="text-rose-600" x-text="formatTanggal(item.end_date)"></strong>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>

      </div>

    </div>
  </x-ui.sidebar>

  @push('scripts')
  <script>
    function dashboard() {
      return {
        loading: false,
        autoRefreshEnabled: true,
        autoRefreshInterval: 30000,
        refreshTimer: null,
        lastUpdated: '{{ now()->format("d M Y H:i") }}',
        stats: @json($stats),

        init() {
          this.startAutoRefresh();
        },

        startAutoRefresh() {
          if (this.refreshTimer) clearInterval(this.refreshTimer);
          this.refreshTimer = setInterval(() => {
            if (this.autoRefreshEnabled && !this.loading) this.refreshStats();
          }, this.autoRefreshInterval);
        },

        async refreshStats() {
          if (this.loading) return;
          this.loading = true;
          try {
            const response = await fetch('{{ route("dashboard.refresh") }}', {
              method: 'POST',
              headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            });
            const data = await response.json();
            if (data.success) { this.stats = data.stats; }
          } catch (error) { console.error('Refresh failed:', error); }
          finally { this.loading = false; }
        },

        formatTanggal(dateString) {
          if (!dateString) return '-';
          return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
          }).format(new Date(dateString));
        }
      }
    }
  </script>
  @endpush
@endsection
@extends('layouts.app')

@section('content')
  <x-ui.sidebar>
    <div
      x-data="dashboard()"
      x-init="init()"
      class="space-y-6"
    >
      {{-- Header --}}
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-emerald-900">Dashboard</h1>
          <p class="text-sm text-slate-600">Ringkasan data dan aktivitas terbaru sistem</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-xs text-slate-500" x-text="'Diperbarui: ' + lastUpdated"></span>
        </div>
      </div>

      {{-- Stats Cards Grid --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
        {{-- Total Mahasantri --}}
        <div class="card bg-white border border-emerald-100 shadow-sm">
          <div class="card-body p-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-medium text-slate-600">Total Mahasantri</h3>
                <p class="text-2xl font-bold text-emerald-800 mt-1" x-text="stats.total_mahasantri">0</p>
              </div>
              <div class="p-3 rounded-full bg-emerald-100 text-emerald-700">
                <x-heroicon-s-academic-cap class="h-6 w-6" />
              </div>
            </div>
            <div class="mt-3">
              <div class="flex items-center text-xs text-slate-500">
                <span class="inline-flex items-center gap-1">
                  <x-heroicon-s-arrow-trending-up class="h-3 w-3 text-emerald-600" />
                  <span x-text="stats.recent_mahasantri_count">0</span> baru (7 hari)
                </span>
              </div>
            </div>
          </div>
        </div>

        {{-- Total Panitia --}}
        <div class="card bg-white border border-emerald-100 shadow-sm">
          <div class="card-body p-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-medium text-slate-600">Total Panitia</h3>
                <p class="text-2xl font-bold text-emerald-800 mt-1" x-text="stats.total_panitia">0</p>
              </div>
              <div class="p-3 rounded-full bg-emerald-100 text-emerald-700">
                <x-heroicon-s-users class="h-6 w-6" />
              </div>
            </div>
            <div class="mt-3">
              <div class="text-xs text-slate-500">
                Tim pengelola sistem
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Jadwal Menunggu Persetujuan (Ketua Panitia) --}}
      @if(auth()->user()->jabatan === 'Ketua Panitia')
      <div class="card bg-white border border-amber-200 shadow-sm">
        <div class="card-body p-4">
          <h3 class="text-lg font-semibold text-amber-700 mb-4">⏳ Jadwal Menunggu Persetujuan</h3>
          @php
            $jadwalMenunggu = \App\Models\JadwalTes::where('status_konfirmasi', 'Menunggu')
                ->select('tanggal', \DB::raw('COUNT(*) as jumlah'))
                ->groupBy('tanggal')
                ->orderBy('tanggal')
                ->get();
          @endphp
          @if($jadwalMenunggu->count() > 0)
          <div class="space-y-2">
            @foreach($jadwalMenunggu as $jm)
            <div class="flex items-center justify-between p-3 bg-amber-50 rounded-lg border border-amber-200">
              <div>
                <span class="font-medium text-amber-800">{{ \Carbon\Carbon::parse($jm->tanggal)->isoFormat('D MMMM Y') }}</span>
                <span class="text-sm text-amber-600 ml-2">({{ $jm->jumlah }} mahasantri)</span>
              </div>
            </div>
            @endforeach
          </div>
          @else
          <p class="text-center text-sm text-slate-400 py-4">Tidak ada jadwal yang menunggu persetujuan</p>
          @endif
        </div>
      </div>
      @endif

      {{-- Info Gelombang --}}
      <div class="card bg-white border border-emerald-100 shadow-sm">
        <div class="card-body p-4">
          <h3 class="text-lg font-semibold text-emerald-900 mb-4">Info Gelombang Pendaftaran</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($stats['gelombang'] as $g)
            <div class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-3">
              <div class="flex items-center gap-2 mb-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">{{ $g->id }}</span>
                <span class="font-medium text-sm text-emerald-900">{{ $g->nama }}</span>
              </div>
              <div class="space-y-1 text-sm text-slate-600">
                <div class="flex items-center gap-2">
                  <x-heroicon-s-calendar-days class="h-4 w-4 text-emerald-500" />
                  <span>Mulai: <strong>{{ \Carbon\Carbon::parse($g->start_date)->isoFormat('D MMMM Y') }}</strong></span>
                </div>
                <div class="flex items-center gap-2">
                  <x-heroicon-s-calendar-days class="h-4 w-4 text-rose-500" />
                  <span>Berakhir: <strong>{{ \Carbon\Carbon::parse($g->end_date)->isoFormat('D MMMM Y') }}</strong></span>
                </div>
              </div>
            </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- Mahasantri Status Chart --}}
      <div class="card bg-white border border-emerald-100 shadow-sm">
        <div class="card-body p-4">
          <h3 class="text-lg font-semibold text-emerald-900 mb-4">Status Mahasantri</h3>
          <div class="space-y-3">
            <template x-for="[status, count] in Object.entries(stats.mahasantri_by_status || {})" :key="status">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-3 h-3 rounded-full" :class="getStatusColor(status)"></div>
                  <span class="text-sm" x-text="status"></span>
                </div>
                <div class="flex items-center gap-3">
                  <span class="text-sm font-medium" x-text="count"></span>
                  <span class="text-xs text-slate-500"
                    x-text="'(' + Math.round((count / stats.total_mahasantri) * 100) + '%)'">
                  </span>
                </div>
              </div>
            </template>
            <div x-show="!Object.keys(stats.mahasantri_by_status || {}).length" class="text-center py-4 text-slate-400">
              Tidak ada data status
            </div>
          </div>
        </div>
      </div>

      {{-- Statistik Beban Kerja Panitia --}}
      <div class="card bg-white border border-emerald-100 shadow-sm">
        <div class="card-body p-4">
          <h3 class="text-lg font-semibold text-emerald-900 mb-4">Statistik Beban Kerja Panitia</h3>
          <div class="overflow-x-auto">
            <table class="table table-sm w-full">
              <thead class="bg-emerald-50">
                <tr class="text-xs font-semibold uppercase tracking-wider text-emerald-800">
                  <th class="text-left">Nama Panitia</th>
                  <th class="text-center">Total Ditugaskan</th>
                  <th class="text-center">Selesai Dinilai</th>
                </tr>
              </thead>
              <tbody>
                <template x-for="item in stats.beban_kerja || []" :key="item.id_panitia">
                  <tr class="border-t border-emerald-100 text-sm">
                    <td class="font-medium text-slate-800" x-text="item.nama_lengkap"></td>
                    <td class="text-center text-slate-600" x-text="item.total_tugas"></td>
                    <td class="text-center text-emerald-600 font-semibold" x-text="item.sudah_dinilai"></td>
                  </tr>
                </template>
                <tr x-show="!stats.beban_kerja || !stats.beban_kerja.length">
                  <td colspan="3" class="text-center py-4 text-slate-400">Belum ada data beban kerja</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- Statistik Pendaftar per Gelombang --}}
      <div class="card bg-white border border-emerald-100 shadow-sm">
        <div class="card-body p-4">
          <h3 class="text-lg font-semibold text-emerald-900 mb-4">Statistik Pendaftar per Gelombang</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="item in stats.gelombang_stats || []" :key="item.nama">
              <div class="rounded-lg border border-emerald-200 bg-emerald-50/30 p-3">
                <div class="flex items-center gap-2 mb-2">
                  <span class="font-semibold text-sm text-emerald-900" x-text="item.nama"></span>
                </div>
                <div class="text-xs text-slate-600 space-y-1">
                  <div x-text="'Periode: ' + item.periode"></div>
                  <div>Jumlah Pendaftar: <strong x-text="item.terdaftar"></strong></div>
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
          console.log('Dashboard initialized');
          this.startAutoRefresh();
          Alpine.effect(() => {
            return () => {
              if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
                this.refreshTimer = null;
              }
            };
          });
        },

        startAutoRefresh() {
          if (this.refreshTimer) clearInterval(this.refreshTimer);
          this.refreshTimer = setInterval(() => {
            if (this.autoRefreshEnabled && !this.loading) this.refreshStats();
          }, this.autoRefreshInterval);
        },

        stopAutoRefresh() {
          if (this.refreshTimer) { clearInterval(this.refreshTimer); this.refreshTimer = null; }
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
            if (data.success) { this.stats = data.stats; this.lastUpdated = data.timestamp; }
          } catch (error) { console.error('Failed to refresh:', error); }
          finally { this.loading = false; }
        },

        toggleAutoRefresh() {
          this.autoRefreshEnabled = !this.autoRefreshEnabled;
          this.autoRefreshEnabled ? this.startAutoRefresh() : this.stopAutoRefresh();
        },

        getStatusColor(status) {
          const colors = {
            'Pendaftar Baru': 'bg-blue-500', 'Lulus': 'bg-emerald-500',
            'Tidak Lulus': 'bg-rose-500', 'Pertimbangan': 'bg-amber-500', 'default': 'bg-slate-500'
          };
          return colors[status] || colors.default;
        },
      }
    }
  </script>
  @endpush
@endsection

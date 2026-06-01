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

      {{-- Charts and Details Section --}}
      <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
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
    </div>
  </x-ui.sidebar>

  @push('scripts')
  <script>
    function dashboard() {
      return {
        loading: false,
        autoRefreshEnabled: true,
        autoRefreshInterval: 30000, // 30 detik
        refreshTimer: null,
        lastUpdated: '{{ now()->format("d M Y H:i") }}',
        stats: @json($stats),
        
        init() {
          console.log('Dashboard initialized');
          this.startAutoRefresh();
          
          // Cleanup timer ketika component di-destroy
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
          if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
          }
          
          this.refreshTimer = setInterval(() => {
            if (this.autoRefreshEnabled && !this.loading) {
              this.refreshStats();
            }
          }, this.autoRefreshInterval);
          
          console.log('Auto-refresh started with interval:', this.autoRefreshInterval, 'ms');
        },
        
        stopAutoRefresh() {
          if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
            console.log('Auto-refresh stopped');
          }
        },
        
        async refreshStats() {
          if (this.loading) return;
          
          this.loading = true;
          try {
            const response = await fetch('{{ route("dashboard.refresh") }}', {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
              },
            });
            
            const data = await response.json();
            if (data.success) {
              this.stats = data.stats;
              this.lastUpdated = data.timestamp;
              // Show subtle visual feedback
              this.showRefreshFeedback();
            }
          } catch (error) {
            console.error('Failed to refresh stats:', error);
          } finally {
            this.loading = false;
          }
        },
        
        showRefreshFeedback() {
          // Add a subtle animation to stats cards to show they were updated
          const cards = document.querySelectorAll('.card');
          cards.forEach(card => {
            card.classList.add('ring-2', 'ring-emerald-200');
            setTimeout(() => {
              card.classList.remove('ring-2', 'ring-emerald-200');
            }, 500);
          });
        },
        
        toggleAutoRefresh() {
          this.autoRefreshEnabled = !this.autoRefreshEnabled;
          if (this.autoRefreshEnabled) {
            this.startAutoRefresh();
          } else {
            this.stopAutoRefresh();
          }
        },
        
        getStatusColor(status) {
          const colors = {
            'Pendaftar Baru': 'bg-blue-500',
            'Lolos': 'bg-emerald-500',
            'Tidak Lolos': 'bg-rose-500',
            'Cadangan': 'bg-amber-500',
            'default': 'bg-slate-500'
          };
          return colors[status] || colors.default;
        },
        
      }
    }
  </script>
  @endpush
@endsection

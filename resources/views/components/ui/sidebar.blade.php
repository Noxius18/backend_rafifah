<div
  x-data="{ open: true, collapsed: false }"
  class="drawer lg:drawer-open"
>
  <input id="sidebar-drawer" type="checkbox" class="drawer-toggle" x-model="open" />

  {{-- Main Content --}}
  <div class="drawer-content flex flex-col min-h-screen bg-emerald-50">
    <x-ui.navbar logo="{{ asset('assets/Logo_1_transparent.png') }}"
                 drawer-id="sidebar-drawer"
                 userName="{{ auth()->user()->nama_lengkap }}"
                 role="{{ auth()->user()->jabatan }}" />
    <main class="p-6 flex-1">
      {{ $slot }}
    </main>
  </div>

  {{-- Sidebar --}}
  <div class="drawer-side z-40">
    <label for="sidebar-drawer" aria-label="close sidebar" class="drawer-overlay lg:hidden"></label>

    <div
      x-bind:class="collapsed ? 'w-16' : 'w-64'"
      class="flex min-h-full flex-col bg-emerald-900 text-emerald-100 transition-all duration-300 overflow-hidden"
    >
      {{-- Header + Toggle Button --}}
      <div class="flex items-center justify-between px-4 py-5 border-b border-emerald-800 min-h-[64px]">
        <span
          x-show="!collapsed"
          x-transition:enter="transition-opacity duration-200 delay-100"
          x-transition:enter-start="opacity-0"
          x-transition:enter-end="opacity-100"
          x-transition:leave="transition-opacity duration-100"
          x-transition:leave-start="opacity-100"
          x-transition:leave-end="opacity-0"
          class="text-xs font-semibold uppercase tracking-wider text-emerald-300 whitespace-nowrap"
        >
          Menu
        </span>

        <button
          x-on:click="collapsed = !collapsed"
          class="hidden lg:flex items-center justify-center w-7 h-7 rounded-md text-emerald-300 hover:bg-emerald-800 hover:text-white transition-colors ml-auto"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 transition-transform duration-300"
            x-bind:class="collapsed ? 'rotate-180' : ''"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7M18 19l-7-7 7-7" />
          </svg>
        </button>
      </div>

      {{-- Navigation --}}
      @php
        $currentRoute = request()->route()->getName();
      @endphp
      <ul class="menu w-full grow gap-1 p-2">

        {{-- Dashboard --}}
        <li class="w-full">
          <a
            href="{{ route('dashboard') }}"
            x-bind:class="collapsed ? 'justify-center' : 'justify-start'"
            x-bind:title="collapsed ? 'Dashboard' : ''"
            class="flex items-center gap-3 rounded-lg px-3 py-2 min-h-[40px] {{ $currentRoute === 'dashboard' ? 'bg-emerald-800 text-emerald-100 hover:bg-emerald-700' : 'text-emerald-100 hover:bg-emerald-800' }}"
          >
            <x-ri-dashboard-fill class="h-5 w-5 shrink-0" />
            <span
              x-show="!collapsed"
              x-transition:enter="transition-opacity duration-200 delay-100"
              x-transition:enter-start="opacity-0"
              x-transition:enter-end="opacity-100"
              x-transition:leave="transition-opacity duration-100"
              x-transition:leave-start="opacity-100"
              x-transition:leave-end="opacity-0"
              class="whitespace-nowrap text-sm"
            >Dashboard</span>
          </a>
        </li>

        {{-- Data Mahasantri --}}
        <li class="w-full">
          <a
            href="{{ route('mahasantri.index') }}"
            x-bind:class="collapsed ? 'justify-center' : 'justify-start'"
            x-bind:title="collapsed ? 'Data Mahasantri' : ''"
            class="flex items-center gap-3 rounded-lg px-3 py-2 min-h-[40px] {{ request()->routeIs('mahasantri.*') ? 'bg-emerald-800 text-emerald-100 hover:bg-emerald-700' : 'text-emerald-100 hover:bg-emerald-800' }}"
          >
            <x-heroicon-s-academic-cap class="h-5 w-5 shrink-0" />
            <span
              x-show="!collapsed"
              x-transition:enter="transition-opacity duration-200 delay-100"
              x-transition:enter-start="opacity-0"
              x-transition:enter-end="opacity-100"
              x-transition:leave="transition-opacity duration-100"
              x-transition:leave-start="opacity-100"
              x-transition:leave-end="opacity-0"
              class="whitespace-nowrap text-sm"
            >Calon Mahasantri</span>
          </a>
        </li>

        {{-- Seleksi & Penilaian --}}
        <li class="w-full">
          <a
            href="{{ route('seleksi.index') }}"
            x-bind:class="collapsed ? 'justify-center' : 'justify-start'"
            x-bind:title="collapsed ? 'Seleksi & Penilaian' : ''"
            class="flex items-center gap-3 rounded-lg px-3 py-2 min-h-[40px] {{ request()->routeIs('seleksi.*') ? 'bg-emerald-800 text-emerald-100 hover:bg-emerald-700' : 'text-emerald-100 hover:bg-emerald-800' }}"
          >
            <x-heroicon-s-pencil class="h-5 w-5 shrink-0" />
            <span
              x-show="!collapsed"
              x-transition:enter="transition-opacity duration-200 delay-100"
              x-transition:enter-start="opacity-0"
              x-transition:enter-end="opacity-100"
              x-transition:leave="transition-opacity duration-100"
              x-transition:leave-start="opacity-100"
              x-transition:leave-end="opacity-0"
              class="whitespace-nowrap text-sm"
            >Seleksi & Penilaian</span>
          </a>
        </li>

        {{-- Panitia — hanya untuk Pengawas --}}
        {{-- Panitia — hanya untuk Pengawas --}}
        {{-- Panitia — hanya untuk Pengawas --}}
        @if(auth()->user()->jabatan === 'Pengawas')
        <li class="w-full">
          <a
            href="{{ route('panitia.index') }}"
            x-bind:class="collapsed ? 'justify-center' : 'justify-start'"
            x-bind:title="collapsed ? 'Panitia' : ''"
            class="flex items-center gap-3 rounded-lg px-3 py-2 min-h-[40px] {{ request()->routeIs('panitia.*') ? 'bg-emerald-800 text-emerald-100 hover:bg-emerald-700' : 'text-emerald-100 hover:bg-emerald-800' }}"
          >
            <x-heroicon-s-users class="h-5 w-5 shrink-0" />
            <span
              x-show="!collapsed"
              x-transition:enter="transition-opacity duration-200 delay-100"
              x-transition:enter-start="opacity-0"
              x-transition:enter-end="opacity-100"
              x-transition:leave="transition-opacity duration-100"
              x-transition:leave-start="opacity-100"
              x-transition:leave-end="opacity-0"
              class="whitespace-nowrap text-sm"
            >Panitia</span>
          </a>
        </li>
        @endif

        {{-- Cetak Laporan — hanya untuk Pengawas, buka di tab baru --}}
        @if(auth()->user()->jabatan === 'Pengawas')
        <li class="w-full">
          <a
            href="{{ route('laporan.cetak-overall') }}"
            target="_blank"
            x-bind:class="collapsed ? 'justify-center' : 'justify-start'"
            x-bind:title="collapsed ? 'Cetak Laporan' : ''"
            class="flex items-center gap-3 rounded-lg px-3 py-2 min-h-[40px] text-emerald-100 hover:bg-emerald-800"
          >
            <x-heroicon-s-document-arrow-down class="h-5 w-5 shrink-0" />
            <span
              x-show="!collapsed"
              x-transition:enter="transition-opacity duration-200 delay-100"
              x-transition:enter-start="opacity-0"
              x-transition:enter-end="opacity-100"
              x-transition:leave="transition-opacity duration-100"
              x-transition:leave-start="opacity-100"
              x-transition:leave-end="opacity-0"
              class="whitespace-nowrap text-sm"
            >Cetak Laporan</span>
          </a>
        </li>
        @endif

        {{-- Logout — trigger form hidden via JS, biar styling <a> sama persis --}}
        {{-- Logout — trigger form hidden via JS, biar styling <a> sama persis --}}
        {{-- Logout — trigger form hidden via JS, biar styling <a> sama persis --}}
        <li class="w-full">
          <a
            href="#"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            x-bind:class="collapsed ? 'justify-center' : 'justify-start'"
            x-bind:title="collapsed ? 'Logout' : ''"
            class="flex items-center gap-3 rounded-lg px-3 py-2 min-h-[40px] text-emerald-100 hover:bg-emerald-800"
          >
            <x-heroicon-s-arrow-left-on-rectangle class="h-5 w-5 shrink-0" />
            <span
              x-show="!collapsed"
              x-transition:enter="transition-opacity duration-200 delay-100"
              x-transition:enter-start="opacity-0"
              x-transition:enter-end="opacity-100"
              x-transition:leave="transition-opacity duration-100"
              x-transition:leave-start="opacity-100"
              x-transition:leave-end="opacity-0"
              class="whitespace-nowrap text-sm"
            >Logout</span>
          </a>
          <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
            @csrf
          </form>
        </li>

      </ul>

      {{-- Footer --}}
      <div
        x-show="!collapsed"
        x-transition:enter="transition-opacity duration-200 delay-100"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="px-4 py-4 text-xs text-emerald-400 border-t border-emerald-800 whitespace-nowrap"
      >
        &copy; {{ date('Y') }} Ma'had Rafifah Andalusia MQ
      </div>
    </div>
  </div>
</div>
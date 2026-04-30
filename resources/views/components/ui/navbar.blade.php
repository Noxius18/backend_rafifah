@props([
  'logo' => '',
  'userName' => 'User',
  'role' => '(Jabatan)',
  'drawerId' => null,
])

<div x-data="{ now: new Date() }" x-init="setInterval(() => now = new Date(), 1000)" class="navbar bg-emerald-700 text-emerald-50 shadow-sm shadow-emerald-500/20">
  <div class="flex items-center gap-3">
    @if ($drawerId)
      <label for="{{ $drawerId }}" class="btn btn-square btn-ghost text-emerald-50 lg:hidden">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round" stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor" class="inline-block h-5 w-5">
          <path d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </label>
    @endif
    <a class="btn normal-case text-xl text-white">
        @if ($logo)
            <img src="{{ $logo }}" alt="Logo" class="h-8 w-auto mr-2">
        @else 
            Ma'had Rafifah Andalusia MQ
        @endif
    </a>
  </div>

  <div class="flex-1 text-center">
    <div class="text-sm md:text-base font-medium" x-text="now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) + ' • ' + now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })"></div>
  </div>

  <div class="flex-none text-right">
    <div class="font-medium text-sm text-emerald-100">Halo, {{ $userName }}</div>
    <div class="text-xs text-emerald-200">{{ $role }}</div>
  </div>
</div>
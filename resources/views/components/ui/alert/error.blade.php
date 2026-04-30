@props([
    'alert'
])

<div role="alert" {{ $attributes->merge(['class' => 'alert alert-error']) }}>
  <x-heroicon-o-exclamation-circle class="stroke-current shrink-0 h-6 w-6" />
  <span>{{ $alert }}</span>
</div>
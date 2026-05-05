@props([
    'id' => 'modal',
    'size' => '', // e.g., 'sm', 'lg', 'xl' for modal-box size variations
    'closeOnBackdrop' => true
])

{{ $trigger ?? '' }}

<dialog id="{{ $id }}" class="modal" {{ $closeOnBackdrop ? 'onclick="if(event.target === this) this.close();"' : '' }}>
  <div class="modal-box {{ $size ? 'max-w-' . $size : '' }}">
    @if($header ?? false)
      <div class="modal-header">
        {{ $header }}
      </div>
    @endif

    <div class="modal-body">
      {{ $body ?? $slot }}
    </div>

    @if($footer ?? false)
      <div class="modal-action">
        {{ $footer }}
      </div>
    @endif
  </div>
</dialog>

@push('scripts')
<script>
  // Optional: Add any custom JS for modal behavior
  document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('{{ $id }}');
    // Additional modal enhancements can be added here
  });
</script>
@endpush
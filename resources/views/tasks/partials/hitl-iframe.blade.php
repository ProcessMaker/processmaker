{{-- Smart Extract Manual Edit content when HITL is enabled --}}
@php
  // The $iframeSrc variable is received as a parameter from edit.blade.php
  $iframeSrc = $iframeSrc ?? null;
@endphp

@if (!empty($iframeSrc))
  <div
    id="manual-edit-iframe-container"
    style="position: relative; width: 100%; height: calc(100vh - 200px); border: none; margin: 0; padding: 0;"
  >
    <x-package-smart-extract::iframe-loader
      :src="$iframeSrc"
      :title="__('Smart Extract')"
      loading-message="{{ __('Loading dashboard') }}"
    />
  </div>
@else
  <div class="alert alert-warning">
    {{ __('No iframe source provided for Manual Edit.') }}
  </div>
@endif

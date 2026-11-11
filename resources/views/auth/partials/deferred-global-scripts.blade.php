@foreach(GlobalScripts::getScripts() as $script)
  @if (
    strpos($script, '/vendor/processmaker/packages/package-dynamic-ui/js/global.js') !== 0
    && ! str_contains($script, '/connectors/package-data-sources/js/shared-components.js')
  )
    <script src="{{ $script }}" defer></script>
  @endif
@endforeach

@foreach(GlobalScripts::getScripts() as $script)
  @if (strpos($script, '/vendor/processmaker/packages/package-dynamic-ui/js/global.js') !== 0)
    <script src="{{ $script }}"></script>
  @endif
@endforeach
<script>
  window.ProcessMaker = window.ProcessMaker || {};
  // No-op bus for package scripts that may load before the full app shell.
  window.ProcessMaker.EventBus = window.ProcessMaker.EventBus || {
    $on: function () {},
    $off: function () {},
    $emit: function () {},
    $once: function () {},
  };
  window.ProcessMaker.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
</script>
<script src="{{ mix('js/translations/index.js') }}" defer></script>

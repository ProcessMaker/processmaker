@foreach(GlobalScripts::getScripts() as $script)
  @if (strpos($script, '/vendor/processmaker/packages/package-dynamic-ui/js/global.js') !== 0)
    <script src="{{ $script }}" defer></script>
  @endif
@endforeach
<script>
  window.ProcessMaker = window.ProcessMaker || {};
  window.ProcessMaker.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
</script>
<script src="{{ mix('js/translations/index.js') }}"></script>

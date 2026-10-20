@include('auth.partials.deferred-global-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    window.ProcessMaker = window.ProcessMaker || {};
    window.ProcessMaker.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
  });
</script>
<script src="{{ mix('js/translations/index.js') }}" defer></script>

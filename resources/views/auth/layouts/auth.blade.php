<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta http-equiv="Content-Security-Policy" content="script-src * 'unsafe-inline' 'unsafe-eval'; object-src 'none';">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="i18n-mdate" content='{!! json_encode(ProcessMaker\i18nHelper::mdates()) !!}'>
  <meta name="settings-translations-enabled" content="{{ config('translations.enabled') ? 'true' : 'false' }}">
  <title>@yield('title') - {{ __('ProcessMaker') }}</title>
  @vite('resources/sass/app.scss')
  @include('auth.partials.login-critical-styles')
  @include('auth.partials.auth-styles')
  <link rel="icon" href="{{ \ProcessMaker\Models\Setting::getFavicon() }}">
  @if (hasPackage('package-accessibility'))
    @include('package-accessibility::userway')
  @endif
  @yield('css')
</head>
<body>
  <div class="background-cover"></div>
  @hasSection('skip-language-selector')
  @else
    @include('auth.partials.language-selector')
  @endif
  <div class="content" id="app">
    <div class="d-flex flex-column" style="min-height: 100vh">
      <div class="flex-fill small-screen">
        <div class="login-layout h-100-vh">
          <div class="login-panel small-screen">
            <div class="card card-body login-container">
              <div class="login-logo">
                @component('components.logo')
                @endcomponent
              </div>
              @yield('content')
            </div>
          </div>
          @php
            $isMobile = (
              isset($_SERVER['HTTP_USER_AGENT'])
              && \ProcessMaker\Helpers\MobileHelper::isMobile($_SERVER['HTTP_USER_AGENT'])
            ) ? true : false;
          @endphp
          @if (!$isMobile)
          <div class="slogan-panel d-none d-lg-flex">
            <div class="slogan">
              <div class="head-text">{{ __("INTELLIGENT BUSINESS ORCHESTRATION") }}</div>
              <div class="display">
                <span class="display-line">{{ __("Built to Master") }}</span>
                <span class="display-line display-complexity">{{ __("Complexity") }}</span>
              </div>
              <div class="subtext">
                {{ __("Orchestrate workflows, systems, and AI at a moment’s notice. Turn constant change into your greatest competitive advantage by giving your team the freedom to build, test, and iterate in real time.") }}
              </div>
            </div>
          </div>
          @endif
        </div>
      </div>
      @php
        $loginFooterSetting = \ProcessMaker\Models\Setting::byKey('login-footer');
      @endphp
      @if ($loginFooterSetting)
        <div class="footer">{!! $loginFooterSetting->config['html'] !!}</div>
      @endif
    </div>
  </div>
  @yield('js')
  @stack('scripts')
  @hasSection('skip-auth-language-scripts')
  @else
    @vite(['resources/js/vite/auth/login.js'])
    @foreach(GlobalScripts::getScripts() as $script)
      @if (strpos($script, '/vendor/processmaker/packages/package-dynamic-ui/js/global.js') !== 0)
        <script src="{{ $script }}" defer></script>
      @endif
    @endforeach
    <script>
      window.ProcessMaker = window.ProcessMaker || {};
      window.ProcessMaker.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
    </script>
    @vite(['resources/js/translations/index.js'])
  @endif
</body>
</html>

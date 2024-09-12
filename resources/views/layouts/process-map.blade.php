<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta http-equiv="Content-Security-Policy" content="script-src * 'unsafe-inline' 'unsafe-eval'; object-src 'none';">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="is-prod" content="{{ config('app.env') == 'production' ? 'true' : 'false' }}">
  <meta name="app-url" content="{{ config('app.url') }}">
  <meta name="i18n-mdate" content='{!! json_encode(ProcessMaker\i18nHelper::mdates()) !!}'>
  @if (Auth::user())
    <meta name="user-id" content="{{ Auth::user()->id }}">
    <meta name="datetime-format" content="{{ Auth::user()->datetime_format ?: config('app.dateformat') }}">
    <meta name="timezone" content="{{ Auth::user()->timezone ?: config('app.timezone') }}">
    @yield('meta')
  @endif
  <meta name="timeout-worker" content="{{ mix('js/timeout.js') }}">
  <meta name="timeout-length"
    content="{{ Session::has('rememberme') && Session::get('rememberme') ? 'Number.MAX_SAFE_INTEGER' : config('session.lifetime') }}">
  <meta name="timeout-warn-seconds" content="{{ config('session.expire_warning') }}">
  @if (Session::has('_alert'))
    <meta name="alert" content="show">
    @php
      [$type, $message] = json_decode(Session::get('_alert'));
      Session::forget('_alert');
    @endphp
    <meta name="alertVariant" content="{{ $type }}">
    <meta name="alertMessage" content="{{ $message }}">
  @endif

  <title>@yield('title', __('Welcome')) - {{ __('ProcessMaker') }}</title>

  <link rel="icon" type="image/png" sizes="16x16" href="{{ \ProcessMaker\Models\Setting::getFavicon() }}">
  <link href="{{ mix('css/app.css') }}" rel="stylesheet">
  <link href="/css/bpmn-symbols/css/bpmn.css" rel="stylesheet">
  @yield('css')
    <script type="text/javascript">
    @if(Auth::user())
      window.Processmaker = {
        csrfToken: "{{csrf_token()}}",
        userId: "{{\Auth::user()->id}}",
        messages: [],
        apiTimeout: {{config('app.api_timeout')}}
      };
      @if(config('broadcasting.default') == 'redis')
        window.Processmaker.broadcasting = {
          broadcaster: "socket.io",
          host: "{{config('broadcasting.connections.redis.host')}}",
          key: "{{config('broadcasting.connections.redis.key')}}"
        };
      @endif
      @if(config('broadcasting.default') == 'pusher')
        window.Processmaker.broadcasting = {
          broadcaster: "pusher",
          key: "{{config('broadcasting.connections.pusher.key')}}",
          cluster: "{{config('broadcasting.connections.pusher.options.cluster')}}",
          forceTLS: {{config('broadcasting.connections.pusher.options.use_tls') ? 'true' : 'false'}},
          debug: {{config('broadcasting.connections.pusher.options.debug') ? 'true' : 'false'}},
          enabledTransports: ['ws', 'wss'],
          disableStats: true,
        };
        
        @if(config('broadcasting.connections.pusher.options.host'))
          window.Processmaker.broadcasting.wsHost = "{{config('broadcasting.connections.pusher.options.host')}}";
          window.Processmaker.broadcasting.wsPort = "{{config('broadcasting.connections.pusher.options.port')}}";
          window.Processmaker.broadcasting.wssPort = "{{config('broadcasting.connections.pusher.options.port')}}";
        @endif

      @endif
    @endif
  </script>
  @isset($addons)
    <script>
      var addons = [];
    </script>
    @foreach ($addons as $addon)
      @if (!empty($addon['script']))
        {!! $addon['script'] !!}
      @endif
    @endforeach
  @endisset

  @if (config('global_header'))
    {!! config('global_header') !!}
  @endif
</head>

<body>
  <div id="app-container" class="d-flex w-100 mw-100 h-100 mh-100">
    <div class="d-flex flex-grow-1 flex-column overflow-hidden">
      <div class="flex-grow-1">
        <div id="navbar"></div>
      </div>
      <div class="flex-grow-1 d-flex flex-column h-100 overflow-hidden" id="mainbody">
        <div id="main" class="main flex-grow-1 h-100 overflow-hidden">
          @yield('content')
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  @if (config('broadcasting.default') == 'redis')
    <script src="{{ config('broadcasting.connections.redis.host') }}/socket.io/socket.io.js"></script>
  @endif
  <script src="{{ mix('js/manifest.js') }}"></script>
  <script src="{{ mix('js/vendor.js') }}"></script>
  <script src="{{ mix('js/app.js') }}"></script>
  <script>
    window.ProcessMaker.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
  </script>
  <script src="{{ mix('js/process-map-layout.js') }}"></script>

  @foreach (GlobalScripts::getScripts() as $script)
    <script src="{{ $script }}"></script>
  @endforeach

  @yield('js')
</body>

</html>

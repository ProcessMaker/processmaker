<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="https://pbs.twimg.com/profile_images/521685439820742657/8B2oQKmP_400x400.jpeg" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ config('app.url') }}">
    <meta name="i18n-mdate" content='{!! json_encode(ProcessMaker\i18nHelper::mdates()) !!}'>
    @yield('meta')
    <meta name="timeout-worker" content="{{ mix('js/timeout.js') }}">
    <meta name="timeout-length" content="{{ config('session.lifetime') }}">
    <meta name="timeout-warn-seconds" content="60">

    <title>@yield('title',__('Welcome')) - {{__('ProcessMaker')}}</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon.png">

    @if(config('broadcasting.broadcaster') == 'socket.io')
        <meta name="broadcaster" content="{{config('broadcasting.broadcaster')}}">
        <meta name="broadcasting-host" content="{{config('broadcasting.host')}}">
        <meta name="broadcasting-key" content="{{config('broadcasting.key')}}">
        <meta name="timeout-worker" content="{{ mix('js/timeout.js') }}">
        <meta name="timeout-length" content="{{ config('session.lifetime') }}">
        <meta name="timeout-warn-seconds" content="60">
    @endif

    @if(Auth::user())
        <meta name="user-id" content="{{ Auth::user()->id }}">
        <meta name="datetime-format" content="{{ Auth::user()->datetime_format ?: config('app.dateformat') }}">
        <meta name="timezone" content="{{ Auth::user()->timezone ?: config('app.timezone') }}">
    @endif

    @yield('css')

    <script type="text/javascript">
    @if(Auth::user())
      window.Processmaker = {
        csrfToken: "{{csrf_token()}}",
        userId: "{{\Auth::user()->id}}",
        messages: @json(\Auth::user()->activeNotifications()),
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
          debug: {{config('broadcasting.connections.pusher.options.debug') ? 'true' : 'false'}}
        };
      @endif
    @endif
  </script>


</head>
<body>
    <div class="container" id="app">
        @yield('content')
    </div>

    <style>
        body, html {
            background-color: transparent;
        }
    </style>
    <!-- Scripts -->
    @if(config('broadcasting.default') == 'redis')
    <script src="{{config('broadcasting.connections.redis.host')}}/socket.io/socket.io.js"></script>
    @endif
    <script src="{{ mix('js/manifest.js') }}"></script>
    <script src="{{ mix('js/vendor.js') }}"></script>
    <script src="{{ mix('js/app.js') }}"></script>
    <!--javascript!-->
    <script>
        window.ProcessMaker.closeSessionModal = function () {}

        // Allow more time for start event file processing
        window.ProcessMaker.apiClient.defaults.timeout = 15000;
    </script>
    @yield('js')
</body>
</html>
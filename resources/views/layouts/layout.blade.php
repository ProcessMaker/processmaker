<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="api-token" content="{{ session('apiToken')['access_token']}}">
    <title>{{ $title or 'Welcome' }} - {{__('ProcessMaker')}}</title>
    <link rel="shortcut icon" type="image/x-icon" href="/img/favicon.ico" />
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    @yield('css')
</head>
<body>
@yield('sidebar')
<div class="container-fluid" id="app-container">
  <div id="top-navbar" style="background-image: url('/img/logo.png')">
      @include('layouts.navbar')
  </div>
  <main role="main" class="main">
    @yield('content')
  </main>
</div>
<div id="api-error" class="error-content">
    <div>
        <h1>Sorry! API failed to load</h1>
        <p>Something went wrong. Try refreshing the application</p>
    </div>
</div>
<!-- Scripts -->
<script src="{{ mix('js/manifest.js') }}"></script>
<script src="{{ mix('js/vendor.js') }}"></script>
<script src="{{ mix('js/app.js') }}"></script>
<script src="{{ mix('js/app-layout.js') }}"></script>
@if(config('broadcasting.broadcaster') == 'socket.io' && config('broadcasting.host') <> '')
    <script src="{{config('broadcasting.host')}}/socket.io/socket.io.js"></script>
    <script type="text/javascript">
    window.Processmaker = {
        csrfToken: "{{csrf_token()}}",
        userId: "{{Auth::id()}}",
        broadcasting: {
            broadcaster: "{{config('broadcasting.broadcaster')}}",
            host: "{{config('broadcasting.host')}}",
            key: "{{config('broadcasting.key')}}"
        }
    }
    </script>
@endif
<script>
    $("#menu-toggle").click(function (e) {
        e.preventDefault();
        $("#wrapper").toggleClass("toggled");
    });
</script>
<!--javascript!-->
@yield('js')
</body>
</html>

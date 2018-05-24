<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- API Token -->
    <meta name="api-token" content="{{ session('apiToken')['access_token']}}">

    <title>ProcessMaker: {{$title}}</title>

    <link rel="shortcut icon" type="image/x-icon" href="/img/favicon.ico" />
    <!-- Styles -->

    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ mix('css/layouts-app.css') }}" rel="stylesheet">

    <script>
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
    @if(config('broadcasting.broadcaster') == 'socket.io')
        <script src="//{{config('broadcasting.host')}}/socket.io/socket.io.js"></script>
    @endif
    @yield('css')
</head>
<body>
<div id="app">
    @include('layouts.navbar')
    @yield('sidebar')

    <div id="page-content-wrapper">
        @yield('content')
    </div>
</div>
<!-- Scripts -->
<script src="{{ mix('js/manifest.js') }}"></script>
<script src="{{ mix('js/vendor.js') }}"></script>
<script src="{{ mix('js/app-layout.js') }}"></script>
<!-- Menu Toggle Script -->
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

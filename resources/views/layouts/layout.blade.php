<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>ProcessMaker: {{$title}}</title>

    <link rel="shortcut icon" type="image/x-icon" href="/img/favicon.ico" />
    <!-- Styles -->

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/layouts-app.css') }}" rel="stylesheet">

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

    @if(session('success_message'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>{{session('success_message')}}</strong> You should check in on some of those fields below.
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
      </div>
    @endif
    @if(session('error_message'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>{{session('error_message')}}</strong> You should check in on some of those fields below.
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
      </div>
    @endif

    <div id="page-content-wrapper">
        @yield('content')
    </div>
</div>
<!-- Scripts -->
<script src="{{ asset('js/manifest.js') }}"></script>
<script src="{{ asset('js/vendor.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
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

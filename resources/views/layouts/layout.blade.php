<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(Auth::user())
    <meta name="user-id" content="{{ Auth::user()->id }}">
    <meta name="datetime-format" content="{{ Auth::user()->datetime_format ?: config('app.dateformat') }}">
    <meta name="timezone" content="{{ Auth::user()->timezone ?: config('app.timezone') }}">
    {{--<meta name="notifications" content='@json(Auth::user()->activeNotifications())'>--}}
    @yield('meta')
    @endif
    @if(config('broadcasting.broadcaster') == 'socket.io')
    <meta name="broadcaster" content="{{config('broadcasting.broadcaster')}}">
    <meta name="broadcasting-host" content="{{config('broadcasting.host')}}">
    <meta name="broadcasting-key" content="{{config('broadcasting.key')}}">
    @endif
    @if(Session::has('_alert'))
      <meta name="alert" content="show">
      @php
      list($type,$message) = json_decode(Session::get('_alert'));
      Session::forget('_alert');
      @endphp
      <meta name="alertVariant" content="{{$type}}">
      <meta name="alertMessage" content="{{$message}}">
    @endif

    <title>@yield('title',__('Welcome')) - {{__('ProcessMaker')}}</title>
    <link rel="shortcut icon" type="image/x-icon" href="/img/favicon.ico" />
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ mix('css/sidebar.css') }}" rel="stylesheet">
    @yield('css')
    <script type="text/javascript">
    window.Processmaker = {
      csrfToken: "{{csrf_token()}}",
      userId: "{{\Auth::user()->id}}",
      messages: @json(\Auth::user()->activeNotifications()),
      broadcasting: {
        broadcaster: "{{config('broadcasting.broadcaster')}}",
        host: "{{config('broadcasting.host')}}",
        key: "{{config('broadcasting.key')}}"
      }
    }
  </script>
</head>

<body>
<div class="d-flex w-100 mw-100 h-100 mh-100" id="app-container">
  <div id="sidebar" :class="{expanded: expanded}">
      @yield('sidebar')
  </div>

  <div class="d-flex flex-grow-1 flex-column" style="overflow: hidden;">
    @include('layouts.navbar')
    <div class="flex-grow-1 d-flex flex-column h-100" id="mainbody">
      <div class="main flex-grow-1">
        @yield('content')
      </div>
    </div>
  </div>
</div>

<div id="api-error" class="error-content">
  <div>
    <h1>Sorry! API failed to load</h1>
    <p>Something went wrong. Try refreshing the application</p>
  </div>
</div>
<!-- Scripts -->
@if(config('broadcasting.broadcaster') == 'socket.io')
<script src="{{config('broadcasting.host')}}/socket.io/socket.io.js"></script>
@endif
<script src="{{ mix('js/manifest.js') }}"></script>
<script src="{{ mix('js/vendor.js') }}"></script>
<script src="{{ mix('js/app.js') }}"></script>
<script src="{{ mix('js/app-layout.js') }}"></script>
    <!--javascript!-->
    @yield('js')
</body>

</html>

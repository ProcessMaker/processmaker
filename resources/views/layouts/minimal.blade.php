<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
@yield('css')
</head>
<body>
    <div class="container" id="app">
@yield('content')
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
@yield('js')
</body>
</html>

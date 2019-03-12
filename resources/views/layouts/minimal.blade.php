<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="https://pbs.twimg.com/profile_images/521685439820742657/8B2oQKmP_400x400.jpeg" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title',__('Welcome')) - {{__('ProcessMaker')}}</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon.png">
@yield('css')
</head>
<body>
    <div class="container" id="app">
@yield('content')
    </div>
@yield('js')
</body>
</html>

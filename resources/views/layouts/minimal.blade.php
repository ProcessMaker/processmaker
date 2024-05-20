<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Security-Policy" content="script-src * 'unsafe-inline' 'unsafe-eval'; object-src 'none';"> 
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="i18n-mdate" content='{!! json_encode(ProcessMaker\i18nHelper::mdates()) !!}'>
    <title>@yield('title',__('Welcome')) - {{ __('ProcessMaker') }}</title>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ \ProcessMaker\Models\Setting::getFavicon() }}">
    @if (hasPackage('package-accessibility'))
        @include('shared.userway')
    @endif
@yield('css')
</head>
<body>
    <div class="container" id="app">
@yield('content')
    </div>
@yield('js')
</body>
</html>

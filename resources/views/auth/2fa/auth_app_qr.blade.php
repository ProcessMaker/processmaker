<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Security-Policy" content="script-src * 'unsafe-inline' 'unsafe-eval'; object-src 'none';">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="i18n-mdate" content='{!! json_encode(ProcessMaker\i18nHelper::mdates()) !!}'>
    <title>{{ __('Configure the authenticator app') }}</title>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ \ProcessMaker\Models\Setting::getFavicon() }}">
</head>
<body>
<div class="content" id="app">
    <div class="d-flex flex-column" style="min-height: 100vh">
        <div class="flex-fill">
            <div class="row" align-v="center">
                <div class="col-md-6 col-lg-7">
                    @php
                        $isMobile = (
                          isset($_SERVER['HTTP_USER_AGENT'])
                          && \ProcessMaker\Helpers\MobileHelper::isMobile($_SERVER['HTTP_USER_AGENT'])
                        ) ? true : false;
                    @endphp
                    @if (!$isMobile)
                        <div class="slogan">
                            <img src="/img/slogan.svg" alt="ProcessMaker" />
                            <img class="sub_logo" src="/img/processmaker_do_more.svg" alt="ProcessMaker" />
                        </div>
                    @endif
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-body p-3">
                        <div style="text-align:center;" class="p-5">
                            @component('components.logo')
                            @endcomponent
                            <p />
                            <div class="row justify-content-between mb-3">
                                <div class="form-group">
                                    {{__('Configure the authenticator app')}}
                                </div>
                                <div class="form-group">
                                    {{__('1.- Download the Google Authenticator App')}}
                                </div>
                                <div class="form-group">
                                    {{__('2.- On the Google Authenticator app click on the + icon')}}
                                </div>
                                <div class="form-group">
                                    {{__('3.- Select "Scan QR code" option')}}
                                </div>
                            </div>
                            <img src="data:image/svg+xml;base64,{{$qrCode}}" alt="QR" />
                        </div>
                        <div class="row justify-content-between mb-3">
                            <button type="button" name="next" class="btn btn-primary btn-block text-capitalize"
                                    dusk="next" onclick="next()">{{ __('Next') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script>
    const browser = navigator.userAgent;
    const isMobileDevice  = /Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i.test(browser);
    document.cookie = "isMobile=false"
    if (isMobileDevice) {
        document.cookie = "isMobile=true"
    }
    var next = function() {
        window.location.href = '/2fa';
    };
</script>
<style>
    .row {
        display: flex;
        flex-wrap: wrap;
        margin-right: 0;
        margin-left: 0;
    }
    .card {
        top: 20%;
        position: relative;
        border-radius: 16px;
    }
    body {
        background-image: url("/img/new_background.png");
        background-repeat: no-repeat;
        background-size: cover;
    }
    .slogan {
        top: 30%;
        position: fixed;
        margin-left: 10%;
        width: 700px;
        font-family: Poppins, sans-serif;
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
    }
    .display {
        color: #FFC107;
        font-size: 61.987px;
        font-weight: 600;
    }
    .sub_logo {
        margin-top: 7%;
    }
</style>
</html>

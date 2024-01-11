<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Security-Policy" content="script-src * 'unsafe-inline' 'unsafe-eval'; object-src 'none';">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="i18n-mdate" content='{!! json_encode(ProcessMaker\i18nHelper::mdates()) !!}'>
    <title>{{ __('Enter Security Code') }}</title>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ \ProcessMaker\Models\Setting::getFavicon() }}">
</head>
<body>
<div class="content" id="app">
    <div class="d-flex flex-column" style="min-height: 100vh">
        <div class="flex-fill">
            <div class="row" align-v="center">
                <div class="col-md-6 col-lg-8">
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
                <div class="col-md-6 col-lg-3">
                    <div class="card card-body p-3">
                        <div style="text-align:center;" class="p-5">
                            @component('components.logo')
                            @endcomponent
                        </div>
                            <form method="POST" class="form" action="{{ route('2fa.validate') }}">
                                @if (session()->has('2fa-message') && !session()->has('2fa-error'))
                                    <div class="alert alert-success">{{ session()->get('2fa-message')}}</div>
                                @endif
                                    @if (session()->has('2fa-error'))
                                        <div class="alert alert-danger">{{ session()->get('2fa-error')}}</div>
                                    @endif

                                <div class="form-group">
                                    <label for="code">{{ __('Enter Security Code') }}</label>
                                    <div class="code-container">
                                        <input
                                            id="code"
                                            type="password"
                                            class="form-control{{ $errors->has('code') ? ' is-invalid' : '' }}"
                                            name="code"
                                            placeholder="{{__('Enter your security code')}}"
                                            required
                                        >
                                        <i class="fa fa-eye" id="toggleCode"></i>
                                        @if ($errors->has('code'))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('code') }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="row justify-content-between mb-3">
                                    <div class="form-group">
                                        <a href="{{ route('2fa.send_again') }}">
                                            {{ __('Send Again') }}
                                        </a>
                                    </div>
                                    @if (in_array(\ProcessMaker\TwoFactorAuthentication::AUTH_APP,
                                        config('password-policies.2fa_method', [])))
                                    <div class="form-group">
                                        <a href="{{ route('2fa.auth_app_qr') }}">
                                            {{ __('Authenticator app') }}
                                        </a>
                                    </div>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <button
                                        type="submit"
                                        name="continue"
                                        class="btn btn-primary btn-block text-uppercase"
                                        dusk="continue"
                                    >
                                        {{ __('Continue') }}
                                    </button>
                                </div>
                            </form>
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

    const toggleCode = document.querySelector('#toggleCode');
    const code = document.querySelector('#code');

    toggleCode.addEventListener('click', function (e) {
        const type = code.getAttribute('type') === 'password' ? 'text' : 'password';
        code.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
    });
</script>
<style>
    .row {
        display: flex;
        flex-wrap: wrap;
        margin-right: 0;
        margin-left: 0;
    }
    .card {
        top: 50%;
        position: relative;
        border-radius: 16px;
    }
    .login-logo-custom,
    .login-logo-default {
        width: 100%;
    }
    .form {
        padding: 0 17px 17px 17px;
    }
    .formContainer {
        width: 504px;
    }
    .formContainer .form {
        margin-top: 85px;
        text-align: left
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
    .footer {
        margin-left: 10%;
    }
    #toggleCode{
        position: absolute;
        top: 28%;
        right: 4%;
        cursor: pointer;
        color: #51585E;
    }
    .code-container {
        position: relative;
    }
    .head-text {
        color: #FFF;
        font-size: 46.067px;
        font-weight: 600;
        font-family: Poppins, sans-serif;
    }
    .display {
        color: #FFC107;
        font-size: 61.987px;
        font-weight: 600;
    }
    .superscript {
        color: #FFF;
        position: relative;
        top: -1.5em;
        font-weight: 600;
        font-family: Poppins, sans-serif;
    }
    .subtext {
        width: 60%;
        color: #FFF;
        font-size: 24.017px;
        font-family: Poppins, sans-serif;
    }
    .sub_logo {
        margin-top: 7%;
    }
</style>
</html>

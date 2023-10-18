<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta http-equiv="Content-Security-Policy" content="script-src * 'unsafe-inline' 'unsafe-eval'; object-src 'none';"> 
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="i18n-mdate" content='{!! json_encode(ProcessMaker\i18nHelper::mdates()) !!}'>
  <title>{{ __('Login') }} - {{ __('ProcessMaker') }}</title>
  <link href="{{ mix('css/app.css') }}" rel="stylesheet">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ \ProcessMaker\Models\Setting::getFavicon() }}">
</head>
<body>
  <div class="content" id="app">
    <div class="d-flex flex-column" style="min-height: 100vh">
      <div class="flex-fill">
        <div class="row" align-v="center">
          <div class="col-md-6 col-lg-8">
            <div class="slogan">
              <img src="/img/slogan.svg" alt="Slogan ProcessMaker" />
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="card card-body p-3">
              <div align="center" class="p-5">
                @component('components.logo')
                @endcomponent
              </div>
              @if (! $block)
              <form method="POST" class="form" action="{{ route('login') }}">
                @if (session()->has('timeout'))
                <div class="alert alert-danger">{{ __("Your account has been timed out for security.") }}</div>
                @endif
                @if (session()->has('login-error'))
                <div class="alert alert-danger">{{ session()->get('login-error')}}</div>
                @endif
                <div class="form-group">
                  <label for="username">{{ __('Username') }}</label>
                  <div class="password-container">
                    <input id="username" type="text" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" name="username" value="{{ old('username') }}" placeholder="{{__('Enter your username')}}" required>
                    @if ($errors->has('username'))
                    <span class="invalid-feedback" role="alert">
                      <strong>{{ $errors->first('username') }}</strong>
                    </span>
                    @endif
                  </div>
                </div>
                <div class="form-group">
                  <label for="password">{{ __('Password') }}</label>
                  <div class="password-container">
                    <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="{{__('Enter your password')}}" required>
                    <i class="fa fa-eye" id="eye"></i>
                    @if ($errors->has('password'))
                    <span class="invalid-feedback" role="alert">
                      <strong>{{ $errors->first('password') }}</strong>
                    </span>
                    @endif
                  </div>
                </div>
                <div class="row justify-content-between mb-3">
                  <div class="form-check">
                    <label class="form-check-label">
                    <input id="remember" class="form-check-input" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} aria-label="{{__('Remember me')}}">
                    {{ __('Remember me') }}</label>
                  </div>
                  <div class="form-group">
                    <a href="{{ route('password.request') }}">
                      {{ __('Forgot Password?') }}
                    </a>
                  </div>
                </div>
                <div class="form-group">
                  <button type="submit" name="login" class="btn btn-primary btn-block text-uppercase" dusk="login">{{ __('Sign In') }}</button>
                </div>
              </form>
              @endif
              @foreach ($addons as $addon)
                @include($addon->view, $addon->data)
              @endforeach
              @if(isset($footer))
                {!! $footer !!}
              @endif
            </div>
          </div>
        </div>
      </div>
      @php
        $loginFooterSetting = \ProcessMaker\Models\Setting::byKey('login-footer');
      @endphp
      @if ($loginFooterSetting)
        <div class="footer">{!! $loginFooterSetting->config['html'] !!}</div>
      @endif
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
  }
  .footer {
    margin-left: 10%;
  }
  #eye{
    position: absolute;
    top: 28%;
    right: 4%;
    cursor: pointer;
    color: #51585E;
  }
  .password-container {
    position: relative;
  }
</style>
</html>


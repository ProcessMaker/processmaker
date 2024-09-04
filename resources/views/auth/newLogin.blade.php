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
  @if (hasPackage('package-accessibility'))
    @include('package-accessibility::userway')
  @endif

</head>
<body>
  <div class="background-cover">
    <img class="background-wave-left" src="/img/gradient-wave-left.svg">
    <img class="background-wave-right" src="/img/gradient-wave-right.svg">
  </div>
  <div class="content" id="app">
    <div class="d-flex flex-column" style="min-height: 100vh">
      <div class="flex-fill small-screen">
        <div id="language-selector"
          class="d-flex justify-content-end position-absolute language-button-container">
          <language-selector-button
            :type="'login'">
          </language-selector-button>
        </div>
        <div class="d-flex justify-content-center align-items-center h-100-vh" align-v="center">
          <div class="col-md-6 col-lg-6 col-xl-7 d-none d-lg-block">
          @php
            $isMobile = (
              isset($_SERVER['HTTP_USER_AGENT'])
              && \ProcessMaker\Helpers\MobileHelper::isMobile($_SERVER['HTTP_USER_AGENT'])
            ) ? true : false;
          @endphp
          @if (!$isMobile)
            <div class="slogan">
              <h2 class="title">{{ __("Business process automation") }}</h2>
              <h1 class="title">{{ __("made") }} <span class="emphasis">{{ __("efficient") }}</span></h1>
              <div class="subhead">
                {{ __("All the tools to empower anyone to quickly automate processes, from custom forms to unique enterprise workflows and complex business rules.") }}
              </div>
            </div>
          @endif
          </div>
          <div class="col-md-6 col-lg-6 col-xl-4 col-xxl-3">
            <div class="card card-body p-2 small-screen login-container">
              <div align="center" class="p-3 pb-4">
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

                @samlidp

                <div class="form-group mb-3">
                  <label for="username">{{ __('Username') }}</label>
                  <div class="password-container">
                    <input id="username" type="text" class="form-control form-control-login {{ $errors->has('username') ? ' is-invalid' : '' }}" name="username" value="{{ old('username') }}" placeholder="{{__('Enter your username')}}" required>
                    @if ($errors->has('username'))
                    <span class="invalid-feedback" role="alert">
                      <strong>{{ $errors->first('username') }}</strong>
                    </span>
                    @endif
                  </div>
                </div>
                <div class="form-group mb-3">
                  <label for="password">{{ __('Password') }}</label>
                  <div class="password-container">
                    <input id="password" type="password" class="form-control form-control-login {{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="{{__('Enter your password')}}" required>
                    <i class="fa fa-eye" id="togglePassword"></i>
                    @if ($errors->has('password'))
                    <span class="invalid-feedback" role="alert">
                      <strong>{{ $errors->first('password') }}</strong>
                    </span>
                    @endif
                  </div>
                </div>
                <div class="row justify-content-between mb-3">
                  <div class="form-check mb-0">
                    <label class="form-check-label">
                    <input id="remember" class="form-check-input" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} aria-label="{{__('Remember me')}}">
                    {{ __('Remember me') }}</label>
                  </div>
                  <div class="form-group mb-0">
                    <a href="{{ route('password.request') }}">
                      {{ __('Forgot Password?') }}
                    </a>
                  </div>
                </div>
                <div class="form-group mb-0">
                  <button type="submit" name="login" class="btn btn-primary btn-block button-login form-control-login" dusk="login">{{ __('Sign In') }}</button>
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

  const togglePassword = document.querySelector('#togglePassword');
  const password = document.querySelector('#password');

  togglePassword.addEventListener('click', function (e) {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.classList.toggle('fa-eye-slash');
});
</script>
<script src="{{ mix('builds/login/js/manifest.js') }}"></script>
<script src="{{ mix('builds/login/js/vendor.js') }}"></script>
<script src="{{ mix('builds/login/js/app-login.js') }}"></script>
@foreach(GlobalScripts::getScripts() as $script)
  <script src="{{$script}}"></script>
@endforeach
<script>
  window.ProcessMaker.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
</script>
<script src="{{ mix('js/translations/index.js') }}"></script>
<style>
  .row {
    display: flex;
    flex-wrap: wrap;
    margin-right: 0;
    margin-left: 0;
  }
  .card {
    /* top: 50%;
    position: relative; */
    border-radius: 16px;
  }
  .login-logo-custom,
  .login-logo-default {
    width: 60%;
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
  .background-cover {
    background-color: black;
    background-repeat: no-repeat;
    background-size: cover;
    position: fixed;
    height: 100%;
    top: 0;
    z-index: -1;
    left: 0;
    width: 100%;
  }
  
  .form-control-login {
    height: 45px;
    padding-bottom: 0;
    padding-top: 0;
  }
  
  .background-wave-left {
      position: fixed;
      bottom: 0;
      left: 0;
  }
  
  .background-wave-right {
      position: fixed;
      top: 0;
      right: 0;
      height: 50%;
  }
  
  body {
    background: transparent;
  }
  .slogan {
    max-width: 600px;
    margin-left: 10%;
    font-family: 'Poppins', sans-serif;
    display: inline-flex;
    flex-direction: column;
    align-items: flex-start;
    text-transform: uppercase;
    text-shadow: 0 0 20px rgba(0, 0, 0, 0.95);
  }

  .slogan .title {
    font-weight: 900;
    color: #ffffff;
  }
  
  .slogan h2.title {
      font-size: 1.4rem;
  }
  
  .slogan h1.title {
      font-size: 3.4rem;
  }

  .slogan .emphasis{
    color: #3982ff;
  }
  .slogan .subhead {
    color: #ffffff;
    font-size: 1.23rem;
    font-weight: 100;
  }
  .footer {
    margin-left: 10%;
  }
  #togglePassword{
    position: absolute;
    top: 32%;
    right: 4%;
    cursor: pointer;
    color: #51585E;
  }
  .password-container {
    position: relative;
  }
  .head-text {
    color: #FFF;
    font-size: 46.067px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
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
    font-family: 'Poppins', sans-serif;
  }
  .subtext {
    width: 60%;
    color: #FFF;
    font-size: 24.017px;
    font-family: 'Poppins', sans-serif;
  }
  
  .button-login {
      text-transform: none;
  }

  .login-container {
    max-width: 500px;
  }
  @media (max-width: 767px) {
    .small-screen {
      border: 0;
      background: white;
    }
    .small-screen.login-container {
      max-width: 100%;
    }
}

body {
  height: unset;
}
.h-100-vh {
  height: 100vh;
}
.language-button-container {
  right: 0;
  margin: 3rem 4rem;
}
</style>
</html>

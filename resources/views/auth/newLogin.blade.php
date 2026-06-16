<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta http-equiv="Content-Security-Policy" content="script-src * 'unsafe-inline' 'unsafe-eval'; object-src 'none';">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="i18n-mdate" content='{!! json_encode(ProcessMaker\i18nHelper::mdates()) !!}'>
  <meta name="settings-translations-enabled" content="{{ config('translations.enabled') ? 'true' : 'false' }}">
  <title>{{ __('Login') }} - {{ __('ProcessMaker') }}</title>
  <link href="{{ mix('css/app.css') }}" rel="stylesheet">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ \ProcessMaker\Models\Setting::getFavicon() }}">
  @if (hasPackage('package-accessibility'))
    @include('package-accessibility::userway')
  @endif

</head>
<body>
  <div class="background-cover">
  </div>
  <div class="content" id="app">
    <div class="d-flex flex-column" style="min-height: 100vh">
      <div class="flex-fill small-screen">
        <div id="language-selector"
          class="d-flex justify-content-end position-absolute language-button-container">
          <language-selector-button
            id="language-login"
            :type="'login'"
            :show-language-code="true">
          </language-selector-button>
        </div>
        <div class="login-layout h-100-vh">
          <div class="login-panel small-screen">
            <div class="card card-body login-container">
              <div class="login-logo">
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
                <div class="login-options mb-3">
                  <div class="form-check mb-0">
                    <label class="form-check-label">
                    <input id="remember" class="form-check-input" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} aria-label="{{__('Remember me')}}">
                    {{ __('Remember me') }}</label>
                  </div>
                  <div class="form-group mb-0">
                    <a class="forgot-password-link" href="{{ route('password.request') }}">
                      {{ __('Forgot Password?') }}
                    </a>
                  </div>
                </div>
                <div class="form-group mb-0">
                  <button type="submit" name="login" class="btn btn-primary btn-block button-login" dusk="login">{{ __('Sign In') }}</button>
                </div>
              </form>
              @endif
              @if (count($addons))
              <div class="login-addons">
                @foreach ($addons as $addon)
                  @include($addon->view, $addon->data)
                @endforeach
              </div>
              @endif
              @if(isset($footer))
                {!! $footer !!}
              @endif
            </div>
          </div>
          @php
            $isMobile = (
              isset($_SERVER['HTTP_USER_AGENT'])
              && \ProcessMaker\Helpers\MobileHelper::isMobile($_SERVER['HTTP_USER_AGENT'])
            ) ? true : false;
          @endphp
          @if (!$isMobile)
          <div class="slogan-panel d-none d-lg-flex">
            <div class="slogan">
              <div class="head-text">{{ __("INTELLIGENT BUSINESS ORCHESTRATION") }}</div>
              <div class="display">
                <span class="display-line">{{ __("Built to Master") }}</span>
                <span class="display-line display-complexity">{{ __("Complexity") }}</span>
              </div>
              <div class="subtext">
                {{ __("Orchestrate workflows, systems, and AI at a moment’s notice. Turn constant change into your greatest competitive advantage by giving your team the freedom to build, test, and iterate in real time.") }}
              </div>
            </div>
          </div>
          @endif
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
  @if (strpos($script, '/vendor/processmaker/packages/package-dynamic-ui/js/global.js') !== 0)
    <script src="{{$script}}"></script>
  @endif
@endforeach
<script>
  window.ProcessMaker.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
</script>
<script src="{{ mix('js/translations/index.js') }}"></script>
<style>
  body {
    background: transparent;
    height: unset;
    font-family: 'Open Sans', sans-serif;
  }

  .row {
    display: flex;
    flex-wrap: wrap;
    margin-right: 0;
    margin-left: 0;
  }

  .login-layout {
    display: flex;
    align-items: center;
    width: 100%;
    min-height: 100vh;
    padding: 2rem 0;
  }

  .login-panel {
    flex: 0 0 auto;
    padding-left: clamp(1.5rem, 11.67vw, 210px);
  }

  .login-container {
    width: 580px;
    max-width: 100%;
    padding: 50px 70px 40px;
    border: none;
    border-radius: 24px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    background: #ffffff;
  }

  .login-logo {
    display: flex;
    justify-content: center;
    margin-bottom: 46px;
  }

  .login-logo-custom,
  .login-logo-default {
    width: 100%;
    max-width: 440px;
    height: auto;
  }

  .form {
    padding: 0;
  }

  .form-group label {
    color: #333333;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
  }

  .background-cover {
    background-color: #002D59;
    background-image: url('{{ asset('img/decisions-bg-pattern.svg') }}');
    background-repeat: repeat;
    background-size: auto;
    background-position: center top;
    position: fixed;
    height: 100%;
    top: 0;
    z-index: -1;
    left: 0;
    width: 100%;
  }

  .form-control-login {
    height: 49px;
    padding: 0 1rem;
    border-radius: 8px;
    border: 1px solid #999999;
    color: #333333;
    font-size: 0.875rem;
  }

  .form-control-login::placeholder {
    color: #808080;
    opacity: 1;
  }

  .form-control-login:focus {
    border-color: #2563EB;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }

  .login-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }

  .form-check-label {
    color: #333333;
    font-size: 0.875rem;
    font-weight: 400;
  }

  .form-check-input {
    width: 27px;
    height: 17px;
    border: 1px solid #666666;
    border-radius: 8px;
  }

  .forgot-password-link {
    color: #2563EB;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    white-space: nowrap;
  }

  .forgot-password-link:hover {
    color: #1d4ed8;
    text-decoration: underline;
  }

  .btn-primary {
    background-color: #2563EB;
    border-color: #2563EB;
  }

  .btn-primary:hover,
  .btn-primary:focus {
    background-color: #1d4ed8;
    border-color: #1d4ed8;
  }

  .button-login {
    height: 50px;
    border-radius: 9px;
    font-weight: 600;
    font-size: 1rem;
    text-transform: none;
  }

  .login-addons {
    margin-top: 1.5rem;
  }

  .login-addons .btn,
  .login-addons a.btn {
    height: 49px;
    border-radius: 8px;
    border: 1px solid #94A1B8;
    background: #ffffff;
    color: #333333;
    font-weight: 500;
  }

  .login-addons hr,
  .login-addons .divider {
    border: 0;
    border-top: 1px solid #E6E6E6;
    margin: 1.5rem 0;
  }

  .login-addons .text-muted,
  .login-addons .divider-text {
    color: #808080 !important;
    font-size: 0.875rem;
  }

  .slogan-panel {
    flex: 1;
    align-items: center;
    padding-left: clamp(2rem, 12.6vw, 227px);
    padding-right: clamp(1.5rem, 5vw, 5rem);
    min-height: 100vh;
  }

  .slogan {
    max-width: 560px;
    font-family: 'Open Sans', sans-serif;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
  }

  .slogan .head-text {
    text-transform: uppercase;
    font-weight: 700;
    color: #A6F252;
    margin: 0 0 1.5rem 0;
    font-size: 0.875rem;
    letter-spacing: 0.28em;
    line-height: 1.4;
  }

  .slogan .display {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    font-size: clamp(3rem, 5.5vw, 5.5rem);
    line-height: 0.95;
    font-weight: 800;
    color: #ffffff;
    margin: 0 0 1.5rem 0;
    max-width: 100%;
  }

  .slogan .display-line {
    display: block;
  }

  .slogan .display-complexity {
    color: #ffffff;
    font-size: clamp(3rem, 5.5vw, 5.5rem);
    font-weight: 800;
    line-height: 0.95;
  }

  .slogan .subtext {
    color: #ffffff;
    font-size: 1rem;
    font-weight: 300;
    line-height: 1.75;
    max-width: 520px;
    margin-top: 0;
  }

  .slogan .display,
  .slogan .subtext {
    text-transform: none;
  }

  .footer {
    margin-left: clamp(1.5rem, 11.67vw, 210px);
  }

  #togglePassword {
    position: absolute;
    top: 50%;
    right: 1rem;
    transform: translateY(-50%);
    cursor: pointer;
    color: #51585E;
  }

  .password-container {
    position: relative;
  }

  .h-100-vh {
    height: 100vh;
  }

  .language-button-container {
    right: 2.4rem;
    top: 2.4rem;
    z-index: 1041;
  }

  @media (max-width: 991px) {
    .login-layout {
      justify-content: center;
      padding: 1.5rem;
    }

    .login-panel {
      padding-left: 0;
      width: 100%;
      display: flex;
      justify-content: center;
    }

    .login-container {
      width: 100%;
      max-width: 580px;
      padding: 2rem 1.5rem;
    }
  }

  @media (max-width: 767px) {
    .small-screen {
      border: 0;
      background: transparent;
    }

    .login-container {
      max-width: 100%;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    }

    .login-options {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.75rem;
    }
  }
</style>
</html>

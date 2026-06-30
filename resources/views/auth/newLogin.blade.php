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
  @include('auth.partials.login-critical-styles')
  @include('auth.partials.auth-styles')
  @include('auth.partials.login-extra-styles')
  <link rel="icon" href="{{ \ProcessMaker\Models\Setting::getFavicon() }}">
  @if (hasPackage('package-accessibility'))
    @include('package-accessibility::userway')
  @endif

</head>
<body>
  <div class="background-cover">
  </div>
  @include('auth.partials.language-selector')
  <div class="content" id="app">
    <div class="d-flex flex-column" style="min-height: 100vh">
      <div class="flex-fill small-screen">
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
                  <div class="password-field-header">
                    <label for="password">{{ __('Password') }}</label>
                    <span id="capsLockWarning" class="caps-lock-warning" hidden aria-live="polite">
                      <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                      {{ __('Caps lock is on') }}
                    </span>
                  </div>
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
                  <label class="login-remember" for="remember">
                    <span class="login-toggle">
                      <input
                        id="remember"
                        class="login-remember-input"
                        type="checkbox"
                        name="remember"
                        {{ old('remember') ? 'checked' : '' }}>
                      <span class="login-toggle-track" aria-hidden="true">
                        <span class="login-toggle-knob"></span>
                      </span>
                    </span>
                    <span class="login-remember-text">{{ __('Remember me') }}</span>
                  </label>
                  <a class="forgot-password-link" href="{{ route('password.request') }}">
                    {{ __('Forgot Password?') }}
                  </a>
                </div>
                <div class="form-group mb-0">
                  <button type="submit" name="login" class="btn btn-primary btn-block button-login button-login-uppercase" dusk="login">{{ __('Login') }}</button>
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
  const capsLockWarning = document.querySelector('#capsLockWarning');

  togglePassword.addEventListener('click', function (e) {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.classList.toggle('fa-eye-slash');
  });

  function updateCapsLockWarning(event) {
    if (!password || !capsLockWarning || !event.getModifierState) {
      return;
    }

    capsLockWarning.hidden = !event.getModifierState('CapsLock');
  }

  if (password && capsLockWarning) {
    password.addEventListener('keydown', updateCapsLockWarning);
    password.addEventListener('keyup', updateCapsLockWarning);
    password.addEventListener('blur', function () {
      capsLockWarning.hidden = true;
    });
  }
</script>
@include('auth.partials.auth-language-scripts')
</html>

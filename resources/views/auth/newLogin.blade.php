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
  <div class="background-cover"></div>
  <div class="content" id="app">
    <div class="d-flex flex-column" style="min-height: 100vh">
      <div class="flex-fill small-screen">
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
              <h1 class="title">{{ __("Smarter processes,") }}</h1>
              <h1 class="title emphasis">{{ __("easier than ever") }}</h1>
              <div class="typewriter-container d-flex align-items-center my-5">
                <img src="/img/proceC2.svg" class="mr-2 procesC2-icon" alt="ProceC2" />
                <div class="typewriter">
                  <p>{{ __('Have you tried the new AI Assistant?') }}</p>
                </div>
              </div>
              <ul class="list">
                <li>{{ __("Create processes from a written description.") }}</li>
                <li>{{ __("Translate into multiple languages.") }}</li>
                <li>{{ __("Search faster.") }}</li>
              </ul>
              <img class="sub_logo" src="/img/processmaker_do_more.svg" alt="ProcessMaker" />
            </div>
          @endif
          </div>
          <div class="col-md-6 col-lg-6 col-xl-4 col-xxl-3">
            <div class="card card-body p-3 small-screen login-container">
              <div align="center" class="p-5">
                @component('components.logo')
                @endcomponent
              </div>
              @if (! $block)
              <form method="POST" class="form" action="{{ route('login') }}" autocomplete="off">
                <input type="text" style="display:none">
                <input type="password" style="display:none" autocomplete="new-password">
                @if (session()->has('timeout'))
                <div class="alert alert-danger">{{ __("Your account has been timed out for security.") }}</div>
                @endif
                @if (session()->has('login-error'))
                <div class="alert alert-danger">{{ session()->get('login-error')}}</div>
                @endif

                @samlidp

                <div class="form-group">
                  <label for="username">{{ __('Username') }}</label>
                  <div class="password-container">
                    <input
                      id="username"
                      type="text"
                      class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}"
                      name="username" value="{{ old('username') }}"
                      placeholder="{{__('Enter your username')}}"
                      required
                      autocomplete="username"
                    >
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
                    <input
                      id="password"
                      type="text"
                      class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}"
                      name="password" placeholder="{{__('Enter your password')}}"
                      required
                      autocomplete="new-password"
                    >
                    <i class="fa fa-eye" id="togglePassword"></i>
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
                  <button type="submit" name="login" class="btn btn-primary btn-block text-uppercase" dusk="login">{{ __('Log In') }}</button>
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

  function setToggle(type) {
    let classToggle = 'fa fa-eye';
    if (type === 'password') {
      classToggle = 'fa fa-eye-slash';
    }
    togglePassword.setAttribute('class', classToggle);
  }
  
  password.addEventListener('keyup', () => {
    let type = 'password';
    if (password.value === '') {
      type = 'text';
    }
    password.setAttribute('type', type);
    this.setToggle(type);
  });
 

  togglePassword.addEventListener('click', function (e) {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.setToggle(type);
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
    /* top: 50%;
    position: relative; */
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
  .background-cover {
    background-image: url(/img/new_background.png);
    background-repeat: no-repeat;
    background-size: cover;
    position: fixed;
    height: 100%;
    top: 0;
    z-index: -1;
    left: 0;
    width: 100%;
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
  }

  .slogan .title {
    font-size: 3.4rem;
    font-weight: 300;
    color: #ffffff;
  }

  .slogan .title.emphasis{
    font-weight: 500;
    color: #FFCA2B;
  }
  .slogan .list {
    list-style: none;
    padding: 0;
    color: #ffffff;
    font-size: 1.23rem;
    font-weight: 100;
  }
  .footer {
    margin-left: 10%;
  }
  #togglePassword{
    position: absolute;
    top: 28%;
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
  .sub_logo {
    margin-top: 7%;
  }
  .procesC2-icon {
    width: 1.8rem;
  }
  .typewriter-container {
    background: #ffffff;
    border-radius: 34px;
    height: 3.5rem;
    padding: 3px 18px;
    width: 100%;
    max-width: 600px;
    animation: slidedown 1s cubic-bezier(0.8, 0.3, 0.01, 1), 3s;
  }
  @keyframes cursor {
    from, to {
      border-color: transparent;
    }
    50% {
      border-color: black;
    }
  }
  @keyframes typing {
    from {
      width: 100%;
    }
    3%, to {
      width: 0;
    }
  }
  @keyframes slidedown {
    from {
      height: 0px;
      visibility: hidden;
      opacity: 0;
    }
    to {
      height: 3.5rem;
      visibility: visible;
      opacity: 1;
    }
  }

  @keyframes slide {
    33.3333333333% {
      font-size: 1.1rem;
    }
    to {
      font-size: 1.1rem;
    }
  }
  .typewriter {
    text-align: center;
    white-space: nowrap;
  }

  .typewriter p {
    position: relative;
    display: inline;
    font-size: 0;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    animation: slide 15s step-start infinite;
  }

  .typewriter p::after {
    content: "";
    position: absolute;
    top: 0;
    right: -3px;
    bottom: 0;
    border-left: 2px solid black;
    background-color: #ffffff;
    animation: typing 18s infinite, cursor 1s infinite;
  }

  .typewriter p:nth-child(1) {
    animation-delay: 1s;
  }
  .typewriter p:nth-child(1)::after {
    animation-delay: 1s;
    animation-timing-function: steps(40), step-end;
  }
  .typewriter p:nth-child(1)::before {
    animation-delay: 0s;
    animation-timing-function: steps(40), step-end;
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
</style>
</html>


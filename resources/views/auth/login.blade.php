@extends('layouts.minimal')
@section('title')
Login
@endsection
@section('content')
<div class="d-flex flex-column" style="min-height: 100vh">
<div class="flex-fill">
  <div align="center" class="p-5">
    @component('components.logo')
    @endcomponent
  </div>

  <div class="row">
    <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
      <div class="card card-body p-3">
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
              <div class="form-group">
                <label for="username">{{ __('Username') }}</label>
                <div>
                  <input
                    id="username"
                    type="text"
                    class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}"
                    name="username"
                    value="{{ old('username') }}"
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
                <div class="">
                  <input
                    id="password"
                    type="text"
                    class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}"
                    name="password"
                    required
                    autocomplete="new-password"
                  >
                  @if ($errors->has('password'))
                    <span class="invalid-feedback" role="alert">
                      <strong>{{ $errors->first('password') }}</strong>
                    </span>
                  @endif
                </div>
              </div>
              <div class="form-check">
                <label class="form-check-label">
              <input id="remember" class="form-check-input" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} aria-label="{{__('Remember me')}}">
              {{ __('Remember me') }}</label>
              </div>
              <div class="form-group">
                <button type="submit" name="login" class="btn btn-success btn-block text-uppercase" dusk="login">{{ __('Log In') }}</button>
              </div>
              <div class="form-group mb-0">
                  <a href="{{ route('password.request') }}">
                    {{ __('Forgot Password?') }}
                  </a>
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
  <div>{!! $loginFooterSetting->config['html'] !!}</div>
@endif

@endsection

@section('js')
    <script>
        const browser = navigator.userAgent;
        const isMobileDevice  = /Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i.test(browser);
        document.cookie = "isMobile=false"
        document.cookie = "firstMounted=false"
        if (isMobileDevice) {
          document.cookie = "isMobile=true"
          document.cookie = "firstMounted=true"
        }

        const password = document.querySelector('#password');

        password.addEventListener('keyup', () => {
          let type = 'password';
          if (password.value === '') {
            type = 'text';
          }
          password.setAttribute('type', type);
        });
    </script>
@endsection

@section('css')
  <style media="screen">
  @media (max-width: 579px) {
    img {
      width: 100%;
    }
  }
  .formContainer {
      width:504px;
  }
  .formContainer .form {
    margin-top:85px;
    text-align: left
  }
  </style>

@endsection

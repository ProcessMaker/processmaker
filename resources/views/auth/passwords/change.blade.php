@extends('layouts.minimal')
@section('title')
Change Password
@endsection
@section('content')
<div class="d-flex flex-column" style="min-height: 100vh">
<div class="flex-fill">
  <div align="center" class="p-5">
    @php
      $loginLogo = \ProcessMaker\Models\Setting::getLogin();
      $isDefault = \ProcessMaker\Models\Setting::loginIsDefault();
      if ($isDefault) {
          $class = 'login-logo-default';
      } else {
          $class = 'login-logo-custom';
      }
    @endphp
    <img src={{$loginLogo}} alt="{{ config('logo-alt-text', 'ProcessMaker') }}" class="{{ $class }}">
  </div>

  <div class="row">
    <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
      <div class="card card-body p-3">

          <form method="PUT" class="form" action="{{ route('password.change') }}">
            <h5 class="mb-3">Please change your account password</h5>
            @if (session()->has('timeout'))
              <div class="alert alert-danger">{{ __("Your account has been timed out for security.") }}</div>
            @endif
            @if (session()->has('login-error'))
              <div class="alert alert-danger">{{ session()->get('login-error')}}</div>
            @endif
              <div class="form-group">
                <label for="password">{{ __('New Password') }}</label>
                <div class="">
                  <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
                  @if ($errors->has('password'))
                    <span class="invalid-feedback">
                      <strong>{{ $errors->first('password') }}</strong>
                    </span>
                  @endif
                </div>
              </div>
              <div class="form-group">
                <label for="password">{{ __('Confirm Password') }}</label>
                <div class="">
                  <input id="password" type="password" class="form-control{{ $errors->has('confpassword') ? ' is-invalid' : '' }}" name="password" required>
                  @if ($errors->has('confpassword'))
                    <span class="invalid-feedback">
                      <strong>{{ $errors->first('confpassword') }}</strong>
                    </span>
                  @endif
                </div>
              </div>
              <div class="form-group">
                <button type="submit" name="login" class="btn btn-primary btn-block text-uppercase" dusk="login">{{ __('Change Password') }}</button>
              </div>
            </form>
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
@section('css')
  <style media="screen">
  .formContainer {
      width:504px;
  }
  .formContainer .form {
    margin-top:85px;
    text-align: left
  }
  </style>

@endsection

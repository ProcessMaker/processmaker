@extends('layouts.minimal')
@section('content')

<div align="center">
  <div class="formContainer">
    <img src="/img/md-blue-logo.png">
    <form method="POST" class="form" action="{{ route('login') }}">
      <div class="form-group">
        <label for="username">{{ __('Username') }}</label>
        <div>
          <input id="username" type="text" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" name="username" value="{{ old('username') }}" required>
          @if ($errors->has('username'))
            <span class="invalid-feedback">
              <strong>{{ $errors->first('username') }}</strong>
            </span>
          @endif
        </div>
      </div>
      <div class="form-group">
        <label for="password">{{ __('Password') }}</label>
        <div class="">
          <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
          @if ($errors->has('password'))
            <span class="invalid-feedback">
              <strong>{{ $errors->first('password') }}</strong>
            </span>
          @endif
        </div>
      </div>
      <div class="form-check">
        <label class="form-check-label">
      <input class="form-check-input" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
      {{ __('Remember me') }}</label>
      </div>
      <div class="form-group">
        <button type="submit" class="btn btn-success btn-block text-uppercase">{{ __('Login') }}</button>
      </div>
      <div class="form-group">
        <small>
          <a href="{{ route('password.request') }}">
            {{ __('Forgot Password?') }}
          </a>
        </small>
      </div>
    </form>
  </div>
</div>
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

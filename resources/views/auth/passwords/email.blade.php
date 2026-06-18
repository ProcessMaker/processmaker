@extends('auth.layouts.auth')

@section('title')
{{ __('Forgot Your Password?') }}
@endsection

@section('content')
<h1 class="auth-card-title">{{ __('Forgot Your Password?') }}</h1>
<p class="auth-card-subtitle">{{ __("Enter your email address and we'll send you a reset link.") }}</p>

<form method="POST" class="form" action="{{ route('password.email') }}">
  @csrf
  @if (session('status'))
  <div class="alert alert-success mb-3">
    {{ session('status') }}
  </div>
  @endif
  <div class="form-group mb-3">
    <label for="email">{{ __('Email Address') }}</label>
    <div class="password-container">
      <input
        id="email"
        type="email"
        class="form-control form-control-login{{ $errors->has('email') ? ' is-invalid' : '' }}"
        name="email"
        value="{{ old('email') }}"
        placeholder="{{ __('Enter your email address') }}"
        required>
      @if ($errors->has('email'))
      <span class="invalid-feedback" role="alert">
        <strong>{{ $errors->first('email') }}</strong>
      </span>
      @endif
    </div>
  </div>
  <div class="form-group mb-3">
    <button type="submit" class="btn btn-primary btn-block button-login">{{ __('Request Reset Link') }}</button>
  </div>
  <div class="form-group mb-0 text-center">
    <a href="{{ route('login') }}" class="auth-link">{{ __('Back to Login') }}</a>
  </div>
</form>
@endsection

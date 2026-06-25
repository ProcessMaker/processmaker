@extends('auth.layouts.auth')

@section('title')
{{ __('Password Assistance') }}
@endsection

@section('content')
<div class="auth-card-header">
  <h1 class="auth-card-title">{{ __('Password Assistance') }}</h1>
  <p class="auth-card-subtitle">{{ __("Please enter your email address and we'll send you a reset link.") }}</p>
</div>

<form method="POST" class="form" action="{{ route('password.email') }}">
  @csrf
  @if (session('status'))
  <div class="alert alert-success mb-3">
    {{ session('status') }}
  </div>
  @endif
  <div class="form-group mb-4">
    <label for="email">{{ __('Email') }}</label>
    <div class="password-container">
      <input
        id="email"
        type="email"
        class="form-control form-control-login{{ $errors->has('email') ? ' is-invalid' : '' }}"
        name="email"
        value="{{ old('email') }}"
        required>
      @if ($errors->has('email'))
      <span class="invalid-feedback" role="alert">
        <strong>{{ $errors->first('email') }}</strong>
      </span>
      @endif
    </div>
  </div>
  <div class="form-group mb-0">
    <button type="submit" class="btn btn-primary btn-block button-login button-login-uppercase">{{ __('Request Reset Link') }}</button>
  </div>
  <div class="auth-card-footer">
    <a href="{{ route('login') }}" class="auth-link">{{ __('Back To Login') }}</a>
  </div>
</form>
@endsection

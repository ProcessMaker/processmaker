@extends('auth.layouts.auth')

@section('title')
{{ __('Reset Your Password') }}
@endsection

@section('content')
<div class="auth-card-header">
  <h1 class="auth-card-title">{{ __('Reset Your Password') }}</h1>
</div>

<form role="form" class="form" method="POST" action="{{ url('/password/reset') }}">
  @csrf
  <input type="hidden" name="token" value="{{ $token }}">
  <div class="form-group mb-3">
    <label for="email">{{ __('Email Address') }}</label>
    <div class="password-container">
      <input
        id="email"
        type="email"
        class="form-control form-control-login{{ $errors->has('email') ? ' is-invalid' : '' }}"
        name="email"
        value="{{ $email ?? old('email') }}"
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
    <label for="username">{{ __('Username') }}</label>
    <div class="password-container">
      <input
        id="username"
        type="text"
        class="form-control form-control-login"
        name="username"
        value="{{ old('username') }}"
        placeholder="{{ __('Enter your username') }}"
        autocomplete="username"
        autocapitalize="none"
        spellcheck="false"
        required>
    </div>
  </div>
  <div class="form-group mb-3">
    <div class="password-field-header">
      <label for="password">{{ __('New Password') }}</label>
      <span id="capsLockWarning" class="caps-lock-warning" hidden aria-live="polite">
        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
        {{ __('Caps lock is on') }}
      </span>
    </div>
    <div class="password-container">
      <input
        id="password"
        type="password"
        class="form-control form-control-login{{ $errors->has('password') ? ' is-invalid' : '' }}"
        name="password"
        placeholder="{{ __('Enter your new password') }}"
        required>
      <i class="fa fa-eye toggle-password" id="togglePassword" aria-hidden="true"></i>
      @if ($errors->has('password'))
      <span class="invalid-feedback" role="alert">
        <strong>{{ $errors->first('password') }}</strong>
      </span>
      @endif
    </div>
  </div>
  <div class="form-group mb-3">
    <label for="password-confirm">{{ __('Confirm New Password') }}</label>
    <div class="password-container">
      <input
        id="password-confirm"
        type="password"
        class="form-control form-control-login"
        name="password_confirmation"
        placeholder="{{ __('Confirm your new password') }}"
        required>
      <i class="fa fa-eye toggle-password" id="togglePasswordConfirm" aria-hidden="true"></i>
    </div>
  </div>
  <div class="form-group mb-0">
    <button type="submit" class="btn btn-primary btn-block button-login button-login-uppercase">{{ __('Reset Password') }}</button>
  </div>
</form>
@endsection

@push('scripts')
<script>
  function bindPasswordToggle(toggleId, inputId) {
    const toggle = document.querySelector(toggleId);
    const input = document.querySelector(inputId);

    if (!toggle || !input) {
      return;
    }

    toggle.addEventListener('click', function () {
      const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
      input.setAttribute('type', type);
      this.classList.toggle('fa-eye-slash');
    });
  }

  bindPasswordToggle('#togglePassword', '#password');
  bindPasswordToggle('#togglePasswordConfirm', '#password-confirm');

  const password = document.querySelector('#password');
  const capsLockWarning = document.querySelector('#capsLockWarning');

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
@endpush

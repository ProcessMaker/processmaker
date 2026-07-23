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
        <svg class="caps-lock-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="14" height="14">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
          <line x1="12" y1="9" x2="12" y2="13"></line>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
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
      <button type="button" id="togglePassword" class="password-toggle-btn" aria-label="{{ __('Toggle password visibility') }}" aria-pressed="false">
        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r-3"></circle>
        </svg>
        <svg class="eye-slash-icon" hidden xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path>
          <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path>
          <line x1="1" y1="1" x2="23" y2="23"></line>
        </svg>
      </button>
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
      <button type="button" id="togglePasswordConfirm" class="password-toggle-btn" aria-label="{{ __('Toggle password visibility') }}" aria-pressed="false">
        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r-3"></circle>
        </svg>
        <svg class="eye-slash-icon" hidden xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path>
          <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path>
          <line x1="1" y1="1" x2="23" y2="23"></line>
        </svg>
      </button>
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
      const isPassword = type === 'password';
      this.querySelector('.eye-icon').hidden = !isPassword;
      this.querySelector('.eye-slash-icon').hidden = isPassword;
      this.setAttribute('aria-pressed', String(!isPassword));
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

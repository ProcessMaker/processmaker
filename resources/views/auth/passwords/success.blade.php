@extends('auth.layouts.auth')

@section('title')
{{ __('Success!') }}
@endsection

@section('content')
<div class="auth-success-message">
  <strong>{{ __('Success!') }}</strong>
  {{ __('Your password has been updated.') }}
</div>
<div class="form-group mb-0">
  <a href="{{ route('logout') }}" class="btn btn-primary btn-block button-login">{{ __('Return to Login') }}</a>
</div>
@endsection

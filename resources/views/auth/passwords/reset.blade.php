@extends('layouts.minimal')

@section('content')
<div align="center">
  <div class="formContainer">
    <img src="/img/md-blue-logo.png">
    <h3>{{__('Reset Your Password')}}</h3>
    <form role="form" method="POST" action="{{ url('/password/reset') }}">
      {{ csrf_field() }} {{-- Needs to be enable when we hook up Controllers <input type="hidden" name="token" value="{{ $token }}"> --}}
      <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
        <label for="password">{{__('New Password')}}</label>
        <input id="password" type="password" class="form-control" name="password">
      </div>
      <div class="form-group{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
        <label for="password-confirm">{{__('Confirm New Password')}}</label>
        <input id="password-confirm" type="password" class="form-control" name="password_confirmation">
      </div>
      <div class="form-group">
        <button type="submit" class="btn btn-success btn-block">{{__('Reset Password')}}</button>
      </div>
    </form>
  </div>
</div>

@endsection
@section('css')
<style media="screen">
  .formContainer {
    width: 400px;
  }

  .formContainer form {
    margin-top: 50px;
  }

  .formContainer h3 {
    margin-top: 52px;
    font-size: 18px;
    font-weight: 600;
  }

  .formContainer button {
    margin-top: 6px;
  }
</style>
@endsection

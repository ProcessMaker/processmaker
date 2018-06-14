@extends('layouts.minimal')

@section('content')
<div class="container mt-5">
  <div class="row">
    <div class="col">
    </div>
    <div class="col-6">
      <div align="center">
        <img class="p-5" src="/img/md-blue-logo.png">
      </div>
      <div align="center">
        <p class="header1">{{__('Forgot Your Password')}}</p>
        <p class="header2">{{__("Enter your email address and we'll send you a reset link.")}}</p>
      </div>
      <div align="center">
        <form method="POST" action="{{ route('password.request') }}" class="bg-light p-4">
          <div class="form-group">
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="input-label">{{ __('E-Mail Address') }}</label>
            <div class="form-group">
                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ $email or old('email') }}" required autofocus>
                @if ($errors->has('email'))
                    <span class="invalid-feedback">
                        <strong>{{ $errors->first('email') }}</strong>
                    </span>
                @endif
            </div>
            <button type="submit" class="btn btn-secondary btn-block mt-3 mb-3 text-light text-uppercase">
              {{ __('REQUEST RESET LINK') }}
            </button>
            <a class=" text-primary input-link" href="{{ route('login') }}">
              {{ __('Back to Login') }}
            </a>
          </div>
        </form>
      </div>
    </div>
    <div class="col">
    </div>
  </div>
</div>
@endsection

@section('css')
<style lang='scss' scoped>
  img{
    margin-top: 90px;
  }
  form{
    width: 304px;
  }
  .header1{
    font-size: 18px;
    font-weight: 500;
  }
  .header2{
    font-size: 14px;
    margin-top: -12px;
    font-weight: 400;
    margin-bottom: 50px;
  }
  .input-label{
    margin-left: -176px;
  }
  .input-link{
    margin-left: -141px;
    font-size: 14px;
    margin-left: -176px;
  }
</style>
@endsection

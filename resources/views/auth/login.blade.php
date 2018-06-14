@extends('layouts.minimal')

@section('content')
<div class="container">
  <div class="row">
    <div class="col">
    </div>
    <div class="mt-5">
      <img class="py-5" src="/img/lg-blue-logo.png" width="320">
      <form class="bg-light mt-3"  method="POST" action="{{ route('login') }}">
        {{ csrf_field() }}
        <div class="form-group">
          <label for="username">{{__('Username')}}</label>
          <input type="text" class="form-control {{ $errors->has('username') ? ' is-invalid' : '' }}" id="username" name="username" value="{{ old('username') }}" required>
            @if ($errors->has('username'))
              <span class="invalid-feedback">
                <strong>{{ $errors->first('username') }}</strong>
              </span>
            @endif
        </div>
        <div class="form-group">
          <label for="InputPassword1">{{__('Password')}}</label>
          <input type="password" class="form-control" id="InputPassword1" placeholder="Password" {{ $errors->has('password') ? ' is-invalid' : '' }} name="password" required>
          @if ($errors->has('password'))
            <span class="invalid-feedback">
              <strong>{{ $errors->first('password') }}</strong>
            </span>
          @endif
        </div>
        <div class="form-group">
          <label class="text" for="password">{{ __('Language') }}</label>
          <select class="form-control">
            <option>English</option>
            <option>Spanish</option>
            <option>Portugese</option>
            <option>Japanese</option>
          </select>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" {{ old('remember') ? 'checked' : '' }}>
          <label class="form-check-label" for="defaultCheck1">
            {{ __('Remember me') }}
          </label>
        </div>
        <button type="submit" class="btn btn-secondary btn-block mb-2 text-light text-uppercase submit-log">
          {{ __('Login') }}
        </button>
        <a class="btn-link text-primary pl-0" href="{{ route('password.request') }}">
          {{ __('Forgot Password?') }}
        </a>
        <div class="center mt-5">
          <p class="text-center mb-0">{{__('Dont have an account?')}}</p>
        </div>
        <div class="text-center">
          <a class="btn-link text-primary pl-0" href="">
            {{__('Start your free trial now!')}}
          </a>
        </div>
      </form>
    </div>
    <div class="col">
    </div>
  </div>
</div>
@endsection

@section('css')
  <style lang="scss" scoped>
  form{
    padding: 29px 32px;
  }
  p{
    font-size: 12px;
    font-weight: 500;
    color: #788793;
  }
  .form-check-label{
    padding-bottom: 16px;
  }
  .form-check-input{
    margin-top: 7px;
  }
  .btn-link{
    font-size: 14px;
  }
  </style>
@endsection

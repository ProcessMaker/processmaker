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

      <form method="POST" action="{{ route('login') }}" class="bg-light p-5">
        {{ csrf_field() }}

      <div class="form-group">
        <label class="text-muted" for="username">{{ __('Username') }}</label>
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
        <label class="text-muted" for="password">{{ __('Password') }}</label>
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
        <label class="text-muted" for="password">{{ __('Language') }}</label>
        <select class="form-control">
          <option>English</option>
          <option>Spanish</option>
          <option>Portugese</option>
          <option>Japanese</option>
        </select>
      </div>

      <div class="checkbox mb-2">
          <label class="text-muted">
              <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> {{ __('Remember me') }}
          </label>
      </div>

      <button type="submit" class="btn btn-secondary btn-block mb-2 text-light text-uppercase">
        {{ __('Login') }}
      </button>

        <a class="btn btn-link text-primary pl-0" href="{{ route('password.request') }}">
          {{ __('Forgot Password?') }}
        </a>
      </form>
    </div>

    <div class="col">
    </div>

  </div>
@endsection

@section('css')
<style>
</style>
@endsection

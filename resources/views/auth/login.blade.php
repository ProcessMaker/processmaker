@extends('layouts.minimal')

@section('content')
  <div class="container p-5 text-center">
    <img class="p-5" src="/img/Logo@2x.png">
  </div>

  <div class= "container bg-light p-5 w-50">
    <div>
      <form method="POST" action="{{ route('login') }}">
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
              <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> {{ __('Remember Me') }}
          </label>
      </div>

      <button type="submit" class="btn btn-primary btn-block mb-2">
        {{ __('Login') }}
      </button>

        <a class="btn btn-link pl-0" href="{{ route('password.request') }}">
          {{ __('Forgot Password?') }}
        </a>
      </form>
    </div>
  </div>

@endsection

@section('css')

@endsection

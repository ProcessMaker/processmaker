@extends('layouts.minimal')

@section('content')
<div class="row h-100">
    <div class="login_background col">
      <div></div>
    </div>
    <div class="col">
        <div class="col-md-8">
            <div>
                <div>
                  <div class="h1 col-sm-4 col-form-label text-md-right">{{ __('Login') }}</div>
                    <form method="POST" action="{{ route('login') }}">
                      {{ csrf_field() }}

                        <div class="form-group">
                            <label class="text-muted" for="username">{{ __('Username') }}</label>
                            <div class="col-md-6">
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
                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>

                                @if ($errors->has('password'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            {{ __('Login') }}
                        </button>

                        <div class="form-inline">
                          <div class="checkbox">
                              <label class="text-muted">
                                  <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> {{ __('Remember Me') }}
                              </label>
                          </div>
                          <a class="btn btn-link" href="{{ route('password.request') }}">
                                {{ __('Forgot Your Password?') }}
                          </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
  <style>
    html {
      height:100%;
    }
    body{
      height:100%
    }
</style>
@endsection

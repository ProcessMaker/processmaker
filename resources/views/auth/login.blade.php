@extends('layouts.minimal')

@section('content')
<div class="row h-100">
    <div class="login_background col">
      <img class="mx-auto img-fluid login_logo"/>
      <h1 class="text-light text-center">Leader in Enterprise</h1>
      <p class="text-light text-center">ProcessMaker has helped us improve the efficiency of our employees. Instead of running around to get approvals, we now have a software based process management.</p>
      <h3 class="text-light text-center">available on</h3>
      <div class="layer"></div>

    </div>

    <div class="col align-self-center p-5">
        <div class="container p-5">
            <div>
                <div>
                  <h3 class="text-center p-3">{{ __('Login') }}</h3>
                    <form method="POST" action="{{ route('login') }}">
                      {{ csrf_field() }}

                        <div class="form-group">
                            <label class="text-muted" for="username">{{ __('Username') }}</label>
                            <div class="">
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
                          <select class="form-control">
                            <option>English</option>
                            <option>Spanish</option>
                            <option>Portugese</option>
                            <option>Japanese</option>
                          </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            {{ __('Login') }}
                        </button>

                        <div class="form-inline float-right">
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
    .login_background{
      background-image: url("/img/processmaker_logo_white.jpg");
      background-repeat: no-repeat;
      background-size: 100% 100%;
      position: absolute;
    }
    .layer{
      background-color: #3397e1BF;
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }
</style>
@endsection

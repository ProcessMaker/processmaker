@extends('layouts.minimal')

@section('content')
<div class="container mt-4">
    <div class="row">

        <div class="col">
        </div>

        <div class="col-md-4">
            <div align="center">
                <img class="py-5" src="/img/lg-blue-logo.png" width="340">
            </div>

            <form method="POST" action="{{ route('login') }}" class="bg-light p2rem mb-5">
                {{ csrf_field() }}

                <div class="form-group">
                    <label class="text" for="username">{{ __('Username') }}</label>
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
                    <label class="text" for="password">{{ __('Password') }}</label>
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
                    <label class="text" for="password">{{ __('Language') }}</label>
                    <select class="form-control">
                        <option>English</option>
                        <option>Spanish</option>
                        <option>Portugese</option>
                        <option>Japanese</option>
                    </select>
                </div>

                <div class="checkbox mb-2">
                    <label class="text">
                        <!--              <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> {{ __('Remember me') }}-->
                        <input class="styled-checkbox" id="styled-checkbox-1" type="checkbox" value="value1" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label for="styled-checkbox-1"> {{ __('Remember me') }}</label>

                    </label>
                </div>

                <button type="submit" class="btn btn-secondary btn-block mb-2 text-light text-uppercase submit-log">
                    {{ __('Login') }}
                </button>

                <a class="btn btn-link text-primary pl-0 font-14" href="{{ route('password.request') }}">
                    {{ __('Forgot Password?') }}
                </a>
                <div class="center mt-5">
                    <p class="text-center mb-0 font-12">Dont have an account?</p>
                </div>
                <div class="text-center">
                    <a class="btn-link text-primary pl-0 font-14" href="">
                        Start your free trial now!
                    </a>
                </div>

            </form>
        </div>

        <div class="col">
        </div>

    </div>
    @endsection

    @section('css')
    <style>

        .p2rem{padding:2rem;}
        .submit-log{font-size: 1rem;padding:0.5rem;}
        .font-12{font-size: 12px;color:#788793;}
        .font-14{font-size: 14px;}
        .styled-checkbox {
            position: absolute;
            opacity: 0;
        }
        .styled-checkbox + label {
            position: relative;
            cursor: pointer;
            padding: 0;
            font-size: 14px;
            color:#788793;
        }
        .styled-checkbox + label:before {
            content: '';
            *margin-right: 5px;
            display: inline-block;
            vertical-align: sub;
            width: 15px;
            height: 15px;
            background: #788793;
            border-radius: 2px;
        }
        .styled-checkbox:hover + label:before {
            background: #788793;
        }
        .styled-checkbox:focus + label:before {
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.12);
        }
        .styled-checkbox:checked + label:before {
            background: #788793;
        }
        .styled-checkbox:disabled + label {
            color: #b8b8b8;
            cursor: auto;
        }
        .styled-checkbox:disabled + label:before {
            box-shadow: none;
            background: #ddd;
        }
        .styled-checkbox:checked + label:after {
            content: '\2714';
            position: absolute;
            left: 3px;
            top: 3px;
            font-size: 11px;
            color: #fff;
            -webkit-transform: rotate(18deg);
            transform: rotate(18deg);
        }
    </style>
    @endsection

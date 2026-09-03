@extends('auth.layouts.two-factor')

@section('title')
{{ __('Enter Security Code') }}
@endsection

@section('css')
<style>
    #toggleCode{
        position: absolute;
        top: 28%;
        right: 4%;
        cursor: pointer;
        color: #51585E;
    }
    .code-container {
        position: relative;
    }
</style>
@endsection

@section('content')
<form method="POST" class="form" action="{{ route('2fa.validate') }}">
    @if (session()->has('2fa-message') && !session()->has('2fa-error'))
        <div class="alert alert-success">{{ session()->get('2fa-message')}}</div>
    @endif
        @if (session()->has('2fa-error'))
            <div class="alert alert-danger">{{ session()->get('2fa-error')}}</div>
        @endif

    <div class="form-group">
        <label for="code">{{ __('Enter Security Code') }}</label>
        <div class="code-container">
            <input
                id="code"
                type="password"
                class="form-control{{ $errors->has('code') ? ' is-invalid' : '' }}"
                name="code"
                placeholder="{{__('Enter your security code')}}"
                required
            >
            <i class="fa fa-eye" id="toggleCode"></i>
            @if ($errors->has('code'))
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $errors->first('code') }}</strong>
                </span>
            @endif
        </div>
    </div>
    <div class="row justify-content-between mb-3">
        <div class="form-group">
            <a href="{{ route('2fa.send_again') }}">
                {{ __('Send Again') }}
            </a>
        </div>
        <div class="form-group text-right">
            @if (in_array(\ProcessMaker\TwoFactorAuthentication::AUTH_APP,
                config('password-policies.2fa_method', [])))
            <a href="{{ route('2fa.auth_app_qr') }}">
                {{ __('Authenticator app') }}
            </a>
            <br>
            @endif
            <a href="{{ route('logout') }}" dusk="login-as-another-user">
                {{ __('Log in as another user') }}
            </a>
        </div>
    </div>
    <div class="form-group">
        <button
            type="submit"
            name="continue"
            class="btn btn-primary btn-block text-uppercase"
            dusk="continue"
        >
            {{ __('Continue') }}
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const browser = navigator.userAgent;
    const isMobileDevice  = /Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i.test(browser);
    document.cookie = "isMobile=false"
    if (isMobileDevice) {
        document.cookie = "isMobile=true"
    }

    const toggleCode = document.querySelector('#toggleCode');
    const code = document.querySelector('#code');

    toggleCode.addEventListener('click', function (e) {
        const type = code.getAttribute('type') === 'password' ? 'text' : 'password';
        code.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
    });
</script>
@endpush

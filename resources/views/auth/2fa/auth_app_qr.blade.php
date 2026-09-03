@extends('auth.layouts.two-factor')

@section('title')
{{ __('Configure the authenticator app') }}
@endsection

@section('content')
<div class="row justify-content-between mb-3">
    <div class="form-group">
        {{__('Configure the authenticator app')}}
    </div>
    <div class="form-group">
        {{__('1.- Download the Google Authenticator App')}}
    </div>
    <div class="form-group">
        {{__('2.- On the Google Authenticator app click on the + icon')}}
    </div>
    <div class="form-group">
        {{__('3.- Select "Scan QR code" option')}}
    </div>
</div>
<img src="data:image/svg+xml;base64,{{$qrCode}}" alt="QR" />
<div class="row justify-content-end mb-3">
    <div class="form-group text-right">
        <a href="{{ route('logout') }}" dusk="login-as-another-user">
            {{ __('Log in as another user') }}
        </a>
    </div>
</div>
<div class="row justify-content-between mb-3">
    <button type="button" name="next" class="btn btn-primary btn-block text-capitalize"
            dusk="next" onclick="next()">{{ __('Next') }}</button>
</div>
@endsection

@push('scripts')
<script>
    const browser = navigator.userAgent;
    const isMobileDevice  = /Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i.test(browser);
    document.cookie = "isMobile=false"
    if (isMobileDevice) {
        document.cookie = "isMobile=true"
    }
    var next = function() {
        window.location.href = '/2fa';
    };
</script>
@endpush

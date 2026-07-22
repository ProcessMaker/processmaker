@php
    $loginLogo = \ProcessMaker\Models\Setting::getLogin();
    $isDefault = \ProcessMaker\Models\Setting::loginIsDefault();
    if ($isDefault) {
        $class = 'login-logo-default';
    } else {
        $class = 'login-logo-custom';
    }
@endphp
<img src="{{ $loginLogo }}" alt="{{ config('logo-alt-text', 'ProcessMaker') }}" class="{{ $class }}" width="440" height="80" fetchpriority="high">

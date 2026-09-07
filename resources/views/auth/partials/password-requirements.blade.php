<div class="alert alert-primary mb-3">{{ __('Password Requirements') }}:
  <ul class="mb-0">
    <li>{{ __('Minimum of :length characters in length', ['length' => (int) config('password-policies.minimum_length', 8)]) }}</li>
    @if (config('password-policies.maximum_length'))
    <li>{{ __('Maximum of :length characters in length', ['length' => (int) config('password-policies.maximum_length')]) }}</li>
    @endif
    @if (config('password-policies.uppercase', true))
    <li>{{ __('Passwords must contain minimum one uppercase character.') }}</li>
    @endif
    @if (config('password-policies.numbers', true))
    <li>{{ __('Passwords must contain minimum one numeric character.') }}</li>
    @endif
    @if (config('password-policies.special', true))
    <li>{{ __('Passwords must contain minimum one special character.') }}</li>
    @endif
  </ul>
</div>

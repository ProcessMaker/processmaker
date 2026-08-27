@extends('auth.layouts.auth')

@section('skip-auth-language-scripts', 'skip')

@section('title')
{{ __('Change Password') }}
@endsection

@section('content')
<div id="changePassword">
  <form method="PUT" class="form" action="" ref="changePasswordForm">
    <div class="text-center pb-4">
      <avatar-image size="75" :input-data="{{ $user }}" hide-name="true"></avatar-image>
      <h5 class="mt-3 mb-0">{{ __('Welcome', ['name' => $user->fullname]) }}</h5>
    </div>
    <div class="auth-card-header">
      <h1 class="auth-card-title">{{ __('Please change your account password') }}</h1>
    </div>
    <div class="alert alert-primary mb-3">{{ __('Password Requirements') }}:
      <ul class="mb-0">
        <li>{{ __('Minimum of 8 characters in length') }}</li>
        <li>{{ __('Contains an uppercase letter') }}</li>
        <li>{{ __('Contains a number or symbol') }}</li>
      </ul>
    </div>
    @if (session()->has('timeout'))
    <div class="alert alert-danger mb-3">{{ __("Your account has been timed out for security.") }}</div>
    @endif
    @if (session()->has('login-error'))
    <div class="alert alert-danger mb-3">{{ session()->get('login-error') }}</div>
    @endif
    <div class="form-group mb-3">
      <label for="password">{{ __('New Password') }}</label>
      <vue-password v-model="formData.password" :disable-toggle=true ref="passwordStrength">
        <div slot="password-input" slot-scope="props">
          {{ html()->password('password')->id('password')->attribute('rows', 4)->class('form-control form-control-login')->attribute('v-model', 'formData.password')->attribute('autocomplete', 'new-password')->attribute('@input', 'props.updatePassword($event.target.value)')->attribute('v-bind:class', '{\'form-control\':true, \'form-control-login\':true, \'is-invalid\':errors.password}') }}
        </div>
      </vue-password>
      <small v-for="(error, index) in errors.password" v-cloak class="text-danger d-block">
        @{{ error }}
      </small>
    </div>
    <div class="form-group mb-3">
      {{ html()->label(__('Confirm Password'), 'confpassword') }}<small class="ml-1">*</small>
      {{ html()->password('confpassword')->class('form-control form-control-login')->attribute('v-model', 'formData.confpassword')->attribute('v-bind:class', '{\'form-control\':true, \'form-control-login\':true}')->attribute('autocomplete', 'new-password') }}
    </div>
    <div class="form-group mb-0">
      <button type="button" @click.prevent="submit" name="changepassword" class="btn btn-primary btn-block button-login button-login-uppercase" dusk="changepassword">{{ __('Change Password') }}</button>
    </div>
  </form>
</div>
@endsection

@section('css')
<style>
  [v-cloak] {
    display: none;
  }
</style>
@endsection

@section('js')
@vite(['resources/js/vite/auth/auth.js'])
@vite(['resources/js/admin/auth/passwords/change.js'])
<script>
window.addEventListener('load', () => {
  var formVueInstance = new Vue({
    el: '#changePassword',
    data() {
      return {
        formData: @json($user),
        userId: @json($user->id),
        errors: {
          password: null,
        },
        currentUserId: {{ Auth::user()->id }},
        options: [{
          src: @json($user['avatar']),
          title: @json($user['fullname']),
          initials: @json(mb_substr($user['firstname'], 0, 1)) + @json(mb_substr($user['lastname'], 0, 1))
        }],
        focusErrors: 'errors',
      }
    },
    methods: {
      resetErrors() {
        this.errors = Object.assign({}, {
          password: null,
        });
      },
      validatePassword() {
        if (!this.formData.password && !this.formData.confpassword) {
          return false;
        }

        if (this.formData.password.trim() === '' && this.formData.confpassword.trim() === '') {
          return false
        }

        if (this.formData.password !== this.formData.confpassword) {
          this.errors.password = ['Passwords must match']
          return false
        }

        this.errors.password = null
        return true
      },
      submit($event) {
        this.resetErrors();

        if (!this.validatePassword()) {
          return false;
        }

        ProcessMaker.apiClient.put('password/change', this.formData)
          .then(response => {
            if (response.status === 200) {
              window.location.href = '/';
            }
          })
          .catch(error => {
            this.errors = error.response.data.errors;
          });
      },
    }
  });
});
</script>
@foreach(GlobalScripts::getScripts() as $script)
  @if (strpos($script, '/vendor/processmaker/packages/package-dynamic-ui/js/global.js') !== 0)
    <script src="{{ $script }}" defer></script>
  @endif
@endforeach
<script>
  window.ProcessMaker = window.ProcessMaker || {};
  window.ProcessMaker.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
</script>
@vite(['resources/js/translations/index.js'])
@endsection

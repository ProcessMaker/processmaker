@extends('layouts.minimal')
@section('title')
{{ __('Change Password') }}
@endsection
@section('content')
<div class="d-flex flex-column" style="min-height: 100vh" id="changePassword">
    <div class="flex-fill">
        <div align="center" class="p-5">
            @php
            $loginLogo = \ProcessMaker\Models\Setting::getLogin();
            $isDefault = \ProcessMaker\Models\Setting::loginIsDefault();
            $class = $isDefault ? 'login-logo-default' : 'login-logo-custom';
            @endphp
            <img src={{ $loginLogo }} alt="{{ config('logo-alt-text', 'ProcessMaker') }}" class="{{ $class }}">
        </div>

        <div class="row">
            <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
                <div class="card card-body p-3">
                    <form method="PUT" class="form" action="" ref="changePasswordForm">
                        <div class="text-center pb-4">
                            <avatar-image size="75" :input-data="{{ $user }}" hide-name="true"></avatar-image>
                            <h5 class="mt-3">{{ __('Welcome', ['name' => $user->fullname]) }}</h5>
                        </div>
                        <h5 class="mb-3">{{ __('Please change your account password') }}</h5>
                        <div class="alert alert-primary">{{ __('Password Requirements')  }}:
                            <ul>
                                <li>{{ __('Minimum of 8 characters in length') }}</li>
                                <li>{{ __('Contains an uppercase letter') }}</li>
                                <li>{{ __('Contains a number or symbol') }}</li>
                            </ul>
                        </div>
                        @if (session()->has('timeout'))
                        <div class="alert alert-danger">{{ __("Your account has been timed out for security.") }}</div>
                        @endif
                        @if (session()->has('login-error'))
                        <div class="alert alert-danger">{{ session()->get('login-error')}}</div>
                        @endif
                        <div class="form-group">
                            <label for="password">{{ __('New Password') }}</label>
                            <vue-password v-model="formData.password" :disable-toggle=true ref="passwordStrength">
                                <div slot="password-input" slot-scope="props">
                                    {{ html()->password('password')->id('password')->attribute('rows', 4)->class('form-control')->attribute('v-model', 'formData.password')->attribute('autocomplete', 'new-password')->attribute('@input', 'props.updatePassword($event.target.value)')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.password}') }}
                                </div>
                            </vue-password>
                            <small v-for="(error, index) in errors.password" v-cloak class="text-danger">
                                @{{ error }}
                            </small>
                        </div>
                        <div class="form-group">
                            {{ html()->label(__('Confirm Password'), 'confpassword') }}<small class="ml-1">*</small>
                            {{ html()->password('confpassword')->class('form-control')->attribute('v-model', 'formData.confpassword')->attribute('v-bind:class', '{\'form-control\':true}')->attribute('autocomplete', 'new-password') }}
                        </div>
                        <div class="form-group pt-3 pb-2">
                            <button type="button" @click.prevent="submit" name="changepassword" class="btn btn-primary btn-block text-uppercase" dusk="changepassword">{{ __('Change Password') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ mix('js/manifest.js') }}"></script>
<script src="{{ mix('js/vue-vendor.js') }}"></script>
<script src="{{ mix('js/fortawesome-vendor.js') }}"></script>
<script src="{{ mix('js/bootstrap-vendor.js') }}"></script>
<script src="{{ mix('js/modeler-vendor.js') }}"></script>
<script src="{{ mix('js/app.js') }}"></script>
<script src="{{ mix('js/admin/auth/passwords/change.js') }}"></script>
<script>
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
</script>
@endsection

@section('css')
  <style media="screen">
      [v-cloak] {
          display: none;
      }

      .formContainer {
          width: 504px;
      }

      .formContainer .form {
        margin-top: 85px;
        text-align: left
      }
  </style>
@endsection

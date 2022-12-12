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
                                    {!! Form::password('password', ['id' => 'password', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                                    => 'formData.password', 'autocomplete' => 'new-password', '@input' => 'props.updatePassword($event.target.value)',
                                    'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.password}']) !!}
                                </div>
                            </vue-password>
                            <small v-if="errors && errors.password && errors.password.length" class="text-danger">@{{ errors.password[0] }}</small>
                        </div>
                        <div class="form-group">
                            {!!Form::label('confpassword', __('Confirm Password'))!!}<small class="ml-1">*</small>
                            {!!Form::password('confpassword', ['class'=> 'form-control', 'v-model'=> 'formData.confpassword',
                            'v-bind:class' => '{\'form-control\':true}', 'autocomplete' => 'new-password'])!!}
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
<script src="{{ mix('js/vendor.js') }}"></script>
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
                if (this.$refs.passwordStrength.strength.score < 2) {
                    this.errors.password = ['Password is too weak']
                    return false
                }

                if (!this.formData.password && !this.formData.confpassword) {
                    return false;
                }

                if (this.formData.password.trim() === '' && this.formData.confpassword.trim() === '') {
                    return false
                }

                if (this.formData.password.trim().length > 0 && this.formData.password.trim().length < 8) {
                    this.errors.password = ['Password must be at least 8 characters']
                    this.formData.password = ''
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
      .formContainer {
          width:504px;
      }

      .formContainer .form {
        margin-top:85px;
        text-align: left
      }
  </style>
@endsection

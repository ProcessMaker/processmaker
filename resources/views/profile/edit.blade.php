@extends('layouts.layout')

@section('title')
    {{__('Edit Profile')}}
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Profile') => route('profile.show', $currentUser->id),
        __('Edit') => null,
    ]])
@endsection
@section('content')
  <div class="container" id="editProfile">
    <div class="row">
      <div class="col-12">
        <nav>
          <div class="nav nav-tabs" id="nav-tab" role="tablist">
          <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home"
            role="tab"
            aria-controls="nav-home" aria-selected="true">{{__('User Info')}}</a>
          <a class="nav-item nav-link" id="nav-accounts-tab" data-toggle="tab" href="#nav-accounts" role="tab"
            aria-controls="nav-accounts" aria-selected="false">{{__('Connected Accounts')}}</a>
          </div>
        </nav>
        <div class="container mt-0 border-top-0 p-3 card card-body">
          <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane show active" id="nav-home" role="tabpanel"
              aria-labelledby="nav-home-tab">
              <div id="profileForm" v-cloak>
                <div class="d-flex flex-column flex-lg-row">
                    <div class="flex-grow-1">
                        @include('shared.users.profile')
                    </div>
                    <div class="ml-lg-3 mt-3 mt-lg-0">
                        @include('shared.users.sidebar')
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                    {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-3', '@click' => 'profileUpdate']) !!}
                </div>
              </div>
            </div>
            <div class="tab-pane" id="nav-accounts" role="tabpanel" aria-labelledby="nav-accounts-tab">
              <div class="flex-grow-1">
                @include('profile.connectedAccounts')
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

    <pm-modal ref="updateAvatarModal" id="updateAvatarModal" title="{{__('Upload Avatar')}}" @hidden="hiddenModal" @ok.prevent="saveAvatar" style="display: none;">
        <div>
            <div v-if="!image" class="no-avatar" align="center">{{__('Click the browse button below to get started')}}</div>
            <div align="center">
                <button type="button" @click="browse" class="btn btn-secondary mt-5 mb-2" ><i class="fas fa-upload"></i>
                    {{__('Browse')}}
                </button>
            </div>
            <div align="center">
                {{__('Image types accepted: .gif, .jpg, .jpeg, .png')}}
            </div>
            <vue-croppie :style="{display: (image) ? 'block' : 'none' }" ref="croppie"
                         :viewport="{ width: 380, height: 380, type: 'circle' }"
                         :boundary="{ width: 400, height: 400 }"
                         :enable-orientation="false" :enable-resize="false">
            </vue-croppie>
        </div>
        <input id="customFile" type="file" class="custom-file-input" accept=".gif,.jpg,.jpeg,.png,image/jpeg,image/gif,image/png" ref="customFile" @change="onFileChange" aria-label="{{__('select file')}}">
    </pm-modal>

    <pm-modal
        ref="editConnectionModal"
        id="editConnectionModal"
        title="{{__('Edit Connection')}}"
        style="display: none;"
        :ok-title="$t('OK')"
        ok-variant="primary"
        @hidden="onCloseModal"
        @close="onCloseModal"
        @onSubmit="onSubmit"
    >
      <div class="form-group">
        {!! Form::label('url', __('URL')) !!}
        {!! Form::text('url', null, ['id' => 'url','class'=> 'form-control', 'v-model' =>
        'formData.url', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.url}',
        'v-bind:placeholder' => '$t("Placeholder")',
        'required', 'aria-required' => 'true']) !!}
        <div class="invalid-feedback" role="alert" v-for="url in errors.url">@{{url}}</div>
      </div>
      <div class="form-group">
        {!! Form::label('user', __('User')) !!}
        {!! Form::text('user', null, ['id' => 'user', 'rows' => 4, 'class'=>
        'form-control', 'v-model' => 'formData.user',
        'v-bind:placeholder' => '$t("Placeholder")',
        'v-bind:class' => '{\'form-control\':true,\'is-invalid\':errors.user}']) !!}
        <div class="invalid-feedback" role="alert" v-for="user in errors.user">
          @{{user}}
        </div>
      </div>
      <div class="form-group">
        {!! Form::label('accessKey', __('Access Key')) !!}
        {!! Form::text('accessKey', null, ['id' => 'accessKey', 'rows' => 4, 'class'=>
        'form-control', 'v-model' => 'formData.accessKey',
        'v-bind:placeholder' => '$t("Placeholder")',
        'v-bind:class' => '{\'form-control\':true,\'is-invalid\':errors.accessKey}']) !!}
        <div class="invalid-feedback" role="alert" v-for="accessKey in errors.accessKey">
          @{{accessKey}}
        </div>
      </div>
    </pm-modal>
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_designer')])
@endsection

@section('js')
	<script src="{{mix('js/admin/profile/edit.js')}}"></script>

<script>
        let formVueInstance = new Vue({
            el: '#editProfile',
            mixins:addons,
            data: {
                meta: @json(config('users.properties')),
                formData: @json($currentUser),
                langs: @json($availableLangs),
                timezones: @json($timezones),
                datetimeFormats: @json($datetimeFormats),
                countries: @json($countries),
                states: @json($states),
                status: @json($status),
                global2FAEnabled: @json($global2FAEnabled),
                ssoUser:@json($ssoUser),
                errors: {
                    username: null,
                    firstname: null,
                    lastname: null,
                    email: null,
                    password: null,
                    status: null
                },
				        confPassword: '',
                image: '',
                originalEmail: '',
                emailHasChanged: false,
                options: [
                    {
                        src: @json($currentUser['avatar']),
                        title: @json($currentUser['fullname']),
                        initials: "{{mb_substr($currentUser['firstname'],0,1, "utf-8")}}" + "{{mb_substr($currentUser['lastname'],0,1, "utf-8")}}"
                    }
                ],
                focusErrors: 'errors',
                accounts: @json($currentUser['connected_accounts']) === null ? []  : @json(json_decode($currentUser['connected_accounts'], true)),
            },
            created() {
              if (this.meta) {
                let keys = Object.keys(this.meta);
                if (!this.formData.meta) {
                    this.formData.meta = {};
                }
                keys.forEach(key => {
                   if (!this.formData.meta[key]) {
                       this.formData.meta[key] = null;
                   }
                });
              }
            },
            mounted() {
              this.originalEmail = this.formData.email;
              const togglePassword = document.querySelector('#togglePassword');
              const password = document.querySelector('#valpassword');

              togglePassword.addEventListener('click', function (e) {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
              });
            },
            methods: {
                openAvatarModal() {
                  modalVueInstance.$refs.updateAvatarModal.show();
                },
                profileUpdate() {
                  if(this.emailHasChanged && !this.ssoUser) {
                    $('#validateModal').modal('show');
                  } else {
                    this.saveProfileChanges();
                  }
                },
                deleteAvatar() {
                    let optionValues = formVueInstance.$data.options[0];
                    optionValues.src = null;
                    formVueInstance.$data.options.splice(0, 1, optionValues)
                    formVueInstance.$data.image = false;
                    formVueInstance.$data.formData.avatar = false;
                    window.ProcessMaker.events.$emit('update-profile-avatar');
                },
                resetErrors() {
                    this.errors = Object.assign({}, {
                        username: null,
                        firstname: null,
                        lastname: null,
                        email: null,
                        password: null,
                        status: null
                    });
                },
                validatePassword() {
                    if (!this.formData.password && !this.formData.confPassword) {
                        delete this.formData.password;
                        return true;
                    }
                    if (this.formData.password.trim() === '' && this.formData.confPassword.trim() === '') {
                        delete this.formData.password;
                        return true
                    }
                    if (this.formData.password !== this.formData.confPassword) {
                        this.errors.password = ['Passwords must match']
                        this.password = ''
                        this.submitted = false
                        return false
                    }
                    return true
                },
                showAccountsModal() {
                  accountsModalInstance.$refs.editConnectionModal.show();
                },
                onClose() {
                  window.location.href = '/admin/users';
                },
                showModal() {
                  $('#validateModal').modal('show');
                },
                closeModal() {
                  $('#validateModal').modal('hide');
                },
                saveProfileChanges() {
                  this.resetErrors();
                    if (@json($enabled2FA) &&  this.global2FAEnabled.length === 0) {
                      let message = 'The Two Step Authentication Method has not been set. ' +
                      'Please contact your administrator.';
                      // User has not enabled two-factor authentication correctly
                      ProcessMaker.alert(this.$t($message), 'warning');
                      return false;
                    }
                    if (!this.validatePassword()) return false;
                    if (@json($enabled2FA) && typeof this.formData.preferences_2fa != "undefined" &&
                        this.formData.preferences_2fa != null && this.formData.preferences_2fa.length < 1)
                          return false;
                    if (this.image) {
                        this.formData.avatar = this.image;
                    }
                    if (this.image === false) {
                        this.formData.avatar = false;
                    }
                    ProcessMaker.apiClient.put('users/' + this.formData.id, this.formData)
                        .then((response) => {
                            ProcessMaker.alert(this.$t('Your profile was saved.'), 'success')
                            window.ProcessMaker.events.$emit('update-profile-avatar');
                            this.originalEmail = this.formData.email;
                            this.emailHasChanged = false;
                            this.formData.valpassword = "";
                        })
                        .catch(error => {
                            this.errors = error.response.data.errors;
                        });

                  this.closeModal();
                },
                checkEmailChange() {
                  this.emailHasChanged = this.formData.email !== this.originalEmail;
                },
            },
            computed: {
                state2FA() {
                    return typeof this.formData.preferences_2fa != "undefined" &&
                        this.formData.preferences_2fa != null && this.formData.preferences_2fa.length > 0;
                },
                disableRecommendations: {
                  get() {
                    return this.formData?.meta?.disableRecommendations ?? false;
                  },
                  set(value) {
                    if (value === true) {
                      if (!this.formData.meta) {
                        this.$set(this.formData, 'meta', {});
                      }
                      this.$set(this.formData.meta, 'disableRecommendations', true);
                    } else {
                      this.$delete(this.formData.meta, 'disableRecommendations');
                    }
                  }
                }
            }
        });
    </script>

    <script>
        let modalVueInstance = new Vue({
            el: '#updateAvatarModal',
            data() {
                return {
                    avatar: formVueInstance.$data.formData.avatar,
                    image: "",
                    idxx: window.ProcessMaker.user.id
                };
            },
            methods: {
                // Called when the croppie instance is completed
                cropResult() {
                },
                saveAvatar() {
                    // We will close our modal, but we will ALSO emit a message stating the image has been updated
                    // The parent component will listen for that message and update it's data to reflect the new image
                    this.$refs.croppie.result({}, (selectedImage) => {
                        // Update the profile's avatar image with the selected one
                        let optionValues = formVueInstance.$data.options[0];
                        optionValues.src = selectedImage;
                        formVueInstance.$data.options.splice(0, 1, optionValues)
                        formVueInstance.$data.formData.avatar = selectedImage;
                        formVueInstance.$data.image = selectedImage;

                        // And finally close the modal
                        this.hideModal();
                    })
                },
                browse() {
                    this.$refs.customFile.click();
                },
                hideModal() {
                    this.$refs.updateAvatarModal.hide();
                },
                hiddenModal() {
                    this.image = '';
                },
                onFileChange(e) {
                    let files = e.target.files || e.dataTransfer.files;
                    if (!files.length) return;
                    this.createImage(files[0]);
                },
                createImage(file) {
                    let reader = new FileReader();

                    // Assigning the load listener to store the contents of the file to our image property
                    reader.onload = e => {
                        // Show we now have an image in our modal to use
                        this.image = true;
                        this.$refs.croppie.bind({
                            url: e.target.result
                        });
                    };
                    // Now actually read it, calling the onload after it's read
                    reader.readAsDataURL(file);
                }
            }
        });
    </script>

    <script>
      var accountsModalInstance = new Vue({
        el: '#editConnectionModal',
        data() {
          return {
            customModalButtons: [
              {"content": "Cancel", "action": "close", "variant": "secondary", "size": "md"},
              {"content": "OK", "action": "onSubmit", "variant": "primary", "size": "md"},
            ],
            formData: {},
            errors: {
              'url': null,
              'user': null,
              'accessKey': null
            },
            disabled: false
          }
        },
        methods: {
          hideModal() {
            accountsModalInstance.$refs.editConnectionModal.hide();
          },
          onCloseModal() {
            this.hideModal();
            this.resetFormData();
            this.resetModalErrors();
          },
          resetFormData() {
            this.formData = Object.assign({}, {
              url: null,
              user: null,
              accessKey: null
            });
          },
          resetModalErrors() {
            this.errors = Object.assign({}, {
              url: null,
              user: null,
              accessKey: null
            });
          },
          onSubmit() {
            this.resetModalErrors();
            //single click
            if (this.disabled) {
              return
            }
            this.disabled = true;

            //TODO: HANDLE CONNECTION UPDATE
            this.onCloseModal;
          },
        }
      });
    </script>
@endsection
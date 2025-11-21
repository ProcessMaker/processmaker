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
                    {{ html()->button(__('Cancel'), 'button')->class('btn btn-outline-secondary')->attribute('@click', 'onClose') }}
                    {{ html()->button(__('Save'), 'button')->class('btn btn-secondary ml-3')->attribute('@click', 'profileUpdate') }}
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
        {{ html()->label(__('URL'), 'url') }}
        {{ html()->text('url')->id('url')->class('form-control')->attribute('v-model', 'formData.url')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.url}')->attribute('v-bind:placeholder', '$t("Placeholder")')->required()->attribute('aria-required', 'true') }}
        <div class="invalid-feedback" role="alert" v-for="url in errors.url">@{{url}}</div>
      </div>
      <div class="form-group">
        {{ html()->label(__('User'), 'user') }}
        {{ html()->text('user')->id('user')->attribute('rows', 4)->class('form-control')->attribute('v-model', 'formData.user')->attribute('v-bind:placeholder', '$t("Placeholder")')->attribute('v-bind:class', '{\'form-control\':true,\'is-invalid\':errors.user}') }}
        <div class="invalid-feedback" role="alert" v-for="user in errors.user">
          @{{user}}
        </div>
      </div>
      <div class="form-group">
        {{ html()->label(__('Access Key'), 'accessKey') }}
        {{ html()->text('accessKey')->id('accessKey')->attribute('rows', 4)->class('form-control')->attribute('v-model', 'formData.accessKey')->attribute('v-bind:placeholder', '$t("Placeholder")')->attribute('v-bind:class', '{\'form-control\':true,\'is-invalid\':errors.accessKey}') }}
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
        const DEFAULT_ACCOUNTS = {
            connectorSlack: {
                name: 'Slack',
                description: 'Send ProcessMaker notifications to Slack',
                icon: 'slack-color-logo',
                enabled: false,
                channel_id: null,
                enabled_at: null,
                ui_options: {
                    show_toggle: true,
                    show_edit_modal: false
                }
            }
        };
        let formVueInstance = new Vue({
            el: '#editProfile',
            mixins:addons,
            data: {
                meta: @json(config('users.properties')),
                formData: @json($currentUser),
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
                slackConfigurationError: false,
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
                  if(this.emailHasChanged) {
                    if (this.ssoUser) {
                      let message = 'Email address for users created via SAML synchronization cannot be edited manually.';
                      ProcessMaker.alert(this.$t(message), 'warning');
                      return;
                    } else {
                      $('#validateModal').modal('show');
                    }
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
                            // reset the slack configuration error
                            this.slackConfigurationError = false;
                            
                            ProcessMaker.alert(this.$t('Your profile was saved.'), 'success')
                            window.ProcessMaker.events.$emit('update-profile-avatar');
                            this.originalEmail = this.formData.email;
                            this.emailHasChanged = false;
                            this.formData.valpassword = "";
                            // Update the data to reflect the updated connected accounts
                            if (document.querySelector('#nav-accounts-tab').classList.contains('active')) {
                              window.location.reload();
                            }
                        })
                        .catch(error => {
                            if (error.response?.data?.errors) {
                                this.errors = error.response.data.errors;
                            }
                            
                            // Handle Slack notification errors
                            if (error.response?.data?.message?.includes('Slack')) {
                                ProcessMaker.alert(this.$t(error.response.data.message), 'danger');
                                // Mark the configuration error state
                                this.slackConfigurationError = true;
                                // Need to ensure the slack toggle is now disabled in the ui
                                this.handleConnectedAccountToggle(DEFAULT_ACCOUNTS.connectorSlack, false, true);
                            }
                        });

                  this.closeModal();
                },
                checkEmailChange() {
                  this.emailHasChanged = this.formData.email !== this.originalEmail;
                },
                handleConnectedAccountToggle(account, $event, error) {
                  try {
                    // If this is a Slack account and we're trying to enable it, validate first
                    if (account.name === 'Slack' && $event === true) {
                      this.validateSlackToken(account, $event);
                      return;
                    }
                    
                    // If the Slack account is being manually disabled, reset the configuration error flag
                    if (account.name === 'Slack' && $event === false && this.slackConfigurationError) {
                      this.slackConfigurationError = false;
                      this.hideSlackMessages();
                    }
                    
                    let accounts = [];
                    if (this.formData.connected_accounts) {
                      accounts = JSON.parse(this.formData.connected_accounts);
                    }
                    
                    const index = accounts.findIndex(acc => acc.name === account.name);
                    if (index !== -1) {
                      // Update existing account
                      accounts[index] = { 
                        ...accounts[index], 
                        enabled: $event,
                        // Update enabled_at when re-enabling
                        enabled_at: $event ? new Date().toISOString() : accounts[index].enabled_at
                      };
                    } else {
                      // Create new account
                      const newAccount = {
                        name: account.name,
                        description: account.description,
                        icon: account.icon,
                        enabled: $event,
                        enabled_at: new Date().toISOString(),
                        channel_id: null,
                        ui_options: {
                          show_toggle: true,
                          show_edit_modal: false
                        }
                      };
                      accounts.push(newAccount);
                    }
                    
                    // Ensure the JSON is properly formatted
                    const jsonString = JSON.stringify(accounts, null, 2);
                
                    // Verify the JSON is valid
                    JSON.parse(jsonString);
                    
                    this.formData.connected_accounts = jsonString;
                    if(!error) {
                      this.saveProfileChanges();
                    }
                  } catch (error) {
                    console.error('Error handling connected account toggle:', error);
                    ProcessMaker.alert(this.$t('Error updating connected account'), 'danger');
                  }
                },
                validateSlackToken(account, $event) {
                  // Show loading state
                  this.showSlackLoadingMessage();
                  
                  // Call the validation endpoint
                  ProcessMaker.apiClient.post('/api/1.0/connector-slack/validate-token')
                    .then((response) => {
                      if (response.data.success) {
                        // Token is valid, proceed with enabling
                        this.hideSlackMessages();
                        
                        this.enableSlackAccount(account, $event);
                        ProcessMaker.alert(this.$t('Slack configuration validated successfully'), 'success');
                      } else {
                        // Token validation failed - check if user is admin based on response
                        const isAdmin = response.data.isAdmin || false;
                        this.showSlackErrorMessage(response.data.error || 'Slack configuration validation failed', isAdmin);
                        // Ensure the toggle stays disabled
                        account.enabled = false;
                        this.$forceUpdate();
                      }
                    })
                    .catch((error) => {
                      console.error('Error validating Slack token:', error);
                      let errorMessage = 'Error validating Slack configuration';
                      let isAdmin = false;
                      
                      // Handle both HTTP errors and validation errors
                      if (error.response?.data?.error) {
                        errorMessage = error.response.data.error;
                      } else if (error.response?.data?.message) {
                        errorMessage = error.response.data.message;
                      }
                      
                      // Check if user is admin based on error response
                      if (error.response?.data?.isAdmin !== undefined) {
                        isAdmin = error.response.data.isAdmin;
                      }
                      
                      this.showSlackErrorMessage(errorMessage, isAdmin);
                      // Ensure the toggle stays disabled and is hidden
                      account.enabled = false;
                    });
                },
                enableSlackAccount(account, $event) {
                  try {
                    let accounts = [];
                    if (this.formData.connected_accounts) {
                      accounts = JSON.parse(this.formData.connected_accounts);
                    }
                    
                    const index = accounts.findIndex(acc => acc.name === account.name);
                    if (index !== -1) {
                      // Update existing account
                      accounts[index] = { 
                        ...accounts[index], 
                        enabled: $event,
                        enabled_at: $event ? new Date().toISOString() : accounts[index].enabled_at
                      };
                    } else {
                      // Create new account
                      const newAccount = {
                        name: account.name,
                        description: account.description,
                        icon: account.icon,
                        enabled: $event,
                        enabled_at: new Date().toISOString(),
                        channel_id: null,
                        ui_options: {
                          show_toggle: true,
                          show_edit_modal: false
                        }
                      };
                      accounts.push(newAccount);
                    }
                    
                    // Ensure the JSON is properly formatted
                    const jsonString = JSON.stringify(accounts, null, 2);
                
                    // Verify the JSON is valid
                    JSON.parse(jsonString);
                    
                    this.formData.connected_accounts = jsonString;
                    this.saveProfileChanges();
                  } catch (error) {
                    console.error('Error enabling Slack account:', error);
                    ProcessMaker.alert(this.$t('Error updating Slack account'), 'danger');
                  }
                },
                showSlackLoadingMessage() {
                  this.hideSlackMessages();
                  
                  const loadingCard = document.createElement('div');
                  loadingCard.id = 'slack-loading-card';
                  loadingCard.className = 'alert alert-info mt-3';
                  loadingCard.innerHTML = `
                    <div class="d-flex align-items-center">
                      <i class="fas fa-spinner fa-spin mr-2"></i>
                      <span>${this.$t('Validating Slack configuration...')}</span>
                    </div>
                  `;
                  
                  this.insertSlackMessage(loadingCard);
                },
                
                showSlackErrorMessage(message, isAdmin = false) {
                  this.hideSlackMessages();
                  
                  // Mark the configuration error state
                  this.slackConfigurationError = true;
                  
                  const cardContent = isAdmin ? this.getAdminCardContent() : this.getUserCardContent();
                  const errorCard = this.createErrorCard(cardContent);
                  
                  this.insertSlackMessage(errorCard);
                },
                
                // Alternative method
                showSlackErrorMessageSimple(message, isAdmin = false) {
                  this.hideSlackMessages();
                  
                  const cardContent = isAdmin ? this.getAdminCardContent() : this.getUserCardContent();
                  const errorCard = this.createErrorCard(cardContent);
                  
                  this.insertSlackMessage(errorCard);
                },
                
                getAdminCardContent() {
                  const envVariables = [
                    'SLACK_BOT_OAUTH_ACCESS_TOKEN',
                    'SLACK_OAUTH_ACCESS_TOKEN'
                  ];
                  
                  const envVariablesHTML = envVariables.map(variable => `
                    <div class="env-variable-box">
                      <code class="env-variable-name">${variable}</code>
                      <button class="copy-btn" onclick="this.copyToClipboard('${variable}')" title="${this.$t('Copy to clipboard')}">
                        <i class="fas fa-copy"></i>
                      </button>
                    </div>
                  `).join('');
                  
                  return `
                    <p class="card-text mb-3">${this.$t('To enable notifications you need to add the appropriate API keys. Please follow these steps to configure it:')}</p>
                    <ol class="steps-list mb-0">
                      <li class="mb-2">${this.$t('Go to the')} <strong>${this.$t('Designer')}</strong> ${this.$t('tab and open the')} 
                        <a href="/designer/environment-variables" target="_blank" class="env-link">
                          ${this.$t('Environment Variables')} <i class="fas fa-external-link-alt"></i>
                        </a> ${this.$t('section.')}
                      </li>
                      <li class="mb-2">${this.$t('Create the following environment variables with your Slack information:')}</li>
                      <li class="mb-2">
                        <div class="env-variables-container">
                          ${envVariablesHTML}
                        </div>
                      </li>
                      <li class="mb-0">${this.$t('After doing it once it will be available for all your users to enable.')}</li>
                    </ol>
                  `;
                },
                
                getUserCardContent() {
                  return `
                    <p class="card-text mb-0">${this.$t('Once a PM Admin configures the integration, you will be able to enable this option to receive your PM notifications in Slack.')}</p>
                  `;
                },
                
                createErrorCard(content) {
                  const errorCard = document.createElement('div');
                  errorCard.id = 'slack-error-card';
                  errorCard.className = 'slack-config-card mt-3';
                  errorCard.innerHTML = `
                    <div class="d-flex align-items-start">
                      <div class="warning-icon mr-3">
                        <span class="exclamation-mark">!</span>
                      </div>
                      <div class="flex-grow-1">
                        <h6 class="card-title mb-2">${this.$t('Slack API Keys required')}</h6>
                        ${content}
                      </div>
                    </div>
                  `;
                  
                  // Add copy method to global context
                  window.copyToClipboard = (text) => {
                    navigator.clipboard.writeText(text).then(() => {
                      // Feedback visual
                      const btn = event.target.closest('.copy-btn');
                      const icon = btn.querySelector('i');
                      const originalClass = icon.className;
                      
                      icon.className = 'fas fa-check text-success';
                      setTimeout(() => {
                        icon.className = originalClass;
                      }, 1000);
                    });
                  };
                  
                  return errorCard;
                },
                hideSlackMessages() {
                  const existingLoading = document.getElementById('slack-loading-card');
                  const existingError = document.getElementById('slack-error-card');
                  
                  if (existingLoading) {
                    existingLoading.remove();
                  }
                  if (existingError) {
                    existingError.remove();
                  }
                },
                insertSlackMessage(messageElement) {
                  // Find the Slack account item in the accounts list
                  const accountsList = document.querySelector('.accounts-list');
                  if (!accountsList) {
                    // Fallback: append to the connected accounts container
                    const container = document.querySelector('#nav-accounts');
                    if (container) {
                      container.appendChild(messageElement);
                    }
                    return;
                  }
                  
                  // Find the Slack account item
                  const slackItem = Array.from(accountsList.children).find(item => {
                    const accountName = item.querySelector('.account-name');
                    return accountName && accountName.textContent.trim() === 'Slack';
                  });
                  
                  if (slackItem) {
                    // Insert the message after the Slack item
                    slackItem.parentNode.insertBefore(messageElement, slackItem.nextSibling);
                  } else {
                    // Fallback: append to the accounts list
                    accountsList.appendChild(messageElement);
                  }
                },
                formatIcon(icon) {
                  return `/img/connected-account-images/${icon}.svg`;
                }
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
                },
                accounts() {
                  let accounts = this.formData.connected_accounts
                    ? JSON.parse(this.formData.connected_accounts) 
                    : [];

                  if (window.ProcessMaker.packages.includes('connector-slack')) {
                    if (!accounts.some(account => account.name === 'Slack')) {
                      accounts.push(DEFAULT_ACCOUNTS.connectorSlack);
                    }
                  }

                  // Apply configuration error state from the reactive property
                  if (this.slackConfigurationError) {
                    const slackAccount = accounts.find(account => account.name === 'Slack');
                    if (slackAccount) {
                      slackAccount.hasConfigurationError = true;
                    }
                  }

                  return accounts;
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

<style>
  /* Slack Configuration Card Styles */
  .slack-config-card {
    background-color: #fdfcf0;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .warning-icon {
    width: 24px;
    height: 24px;
    background-color: #ffc107;
    border: 1px solid #e0a800;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .exclamation-mark {
    color: #856404;
    font-weight: bold;
    font-size: 14px;
    line-height: 1;
  }

  .card-title {
    color: #333;
    font-weight: bold;
    font-size: 16px;
    margin: 0;
  }

  .card-text {
    color: #333;
    font-size: 14px;
    line-height: 1.5;
    margin: 0;
  }

  .steps-list {
    color: #333;
    font-size: 14px;
    line-height: 1.5;
    padding-left: 20px;
  }

  .steps-list li {
    margin-bottom: 8px;
  }

  .env-link {
    color: #007bff;
    text-decoration: underline;
  }

  .env-link:hover {
    color: #0056b3;
    text-decoration: underline;
  }

  .env-variables-container {
    margin-top: 8px;
  }

  .env-variable-box {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .env-variable-name {
    color: #333;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    background: none;
    border: none;
    padding: 0;
  }

  .copy-btn {
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: background-color 0.2s;
  }

  .copy-btn:hover {
    background-color: #e9ecef;
  }

  .copy-btn i {
    font-size: 12px;
  }
</style>
@endsection

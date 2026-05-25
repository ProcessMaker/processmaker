@extends('layouts.layout')

@section('title')
    {{__('Auth Clients')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Auth Clients') => null,
    ]])
@endsection
@section('content')
    <div id="authClients">
        <pm-modal ref="createEditAuthClient" id="createEditAuthClient" :title="title" @hidden="onClose" @ok.prevent="onSave" style="display: none;">
            <required></required>
            <div class="form-group" required>
                {{ html()->label(__('Name'), 'name') }}
                {{ html()->text('name')->class('form-control')->attribute('v-model', 'authClient.name')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.name}')->required()->attribute('aria-required', 'true') }}
                <small class="form-text text-muted">{{ __('Name must be unique') }}</small>
                <div class="invalid-feedback" role="alert" v-if="errors.name">@{{ errors.name[0] }}</div>
            </div>
            <b-form-checkbox-group v-model="authClient.types" required>
              <div class="form-group">
                <div class="invalid-feedback d-block" v-if="errors.types">@{{ errors.types[0] }}</div>
                <b-form-checkbox value="authorization_code_grant">{{__('Enable Authorization Code Grant')}}</b-form-checkbox>
                <br />
                <template v-if="authClient['types'].includes('authorization_code_grant')">
                  {{ html()->label(__('Redirect URL'), 'redirect') }}
                  {{ html()->text('redirect')->class('form-control')->attribute('v-model', 'authClient.redirect')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.redirect}')->attribute('rows', 3) }}
                  <div class="invalid-feedback" role="alert" v-if="errors.redirect">@{{ errors.redirect[0] }}</div>
                </template>
              </div>
              <div class="form-group">
                <b-form-checkbox value="password_client">{{__('Enable Password Grant')}}</b-form-checkbox>
              </div>
            </b-form-checkbox-group>
        </pm-modal>
        <pm-modal ref="secretModal" id="secretModal" :title="secretTitle" style="display: none;"
        :set-custom-buttons="true"
        :custom-buttons="customModalButtons"
        @close="hideSecretModal">
            <div class="form-group">
                <label for="secret">{{__('Secret')}}</label>
                <div class="input-group">
                    <input readonly disabled type="text" class="form-control" id="secret" v-model="secret">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-primary" @click="copySecret(secret)" v-b-tooltip.hover :title="$t('Copy Secret To Clipboard')">
                            <i class="fa-lg fas fa-copy" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="tw-flex tw-items-start tw-gap-3 tw-p-3 tw-mt-3 tw-rounded-lg tw-border tw-border-amber-300 tw-bg-amber-50 tw-text-amber-900" role="alert">
                <i class="fas fa-exclamation-triangle tw-text-amber-500 tw-mt-0.5 tw-shrink-0" aria-hidden="true"></i>
                <p class="tw-text-sm tw-m-0">{{ __('This is the only time you will be able to view the client secret. Keep it in a safe place.') }}</p>
            </div>
        </pm-modal>

        <div class="px-3 page-content">
            <div id="search-bar" class="search mb-3" vcloak>
                <div class="d-flex flex-column flex-md-row">
                    <div class="flex-grow-1">
                        <div id="search" class="mb-3 mb-md-0">
                            <div class="input-group w-100">
                                <input id="search-box" v-model="filter" class="form-control" placeholder="{{__('Search')}}" aria-label="{{__('Search')}}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex ml-md-2 flex-column flex-md-row">
                        <button class="btn btn-secondary" @click="$refs.createEditAuthClient.show()" aria-label="{{__('Create Auth Client')}}">
                            <i class="fas fa-plus"></i>
                            {{__('Auth Client')}}
                        </button>
                    </div>
                </div>
            </div>
            <div class="container-fluid">
                <auth-clients-listing ref="authClientList" :permission="{{ \Auth::user()->hasPermissionsFor('auth_clients') }}" :filter="filter" @edit="edit"/>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/auth-clients/index.js')}}"></script>

    <script>
      new Vue({
        el: '#authClients',
        data: {
          authClient: {
            id: null,
            name: "",
            types: [],
            redirect: "",
            secret: "",
          },
          filter: "",
          errors: null,
          disabled: false,
          title:'',
          secretTitle:'',
          customModalButtons: [],
          secret: "",
        },
        beforeMount() {
          this.initCustomModalButtons();
          this.resetValues();
        },
        methods: {
          onClose() {
            this.resetValues();
          },
          onSave() {
            //single click
            if (this.disabled) {
              return
            }
            this.disabled = true;

            this.loading = true
            let method = 'POST'
            let url = '/oauth/clients'
            let verb = 'created'
            if (this.authClient.id) {
              // Do an update
              method = 'PUT',
                url = url + '/' + this.authClient.id
              verb = 'saved'
            }
            ProcessMaker.apiClient({
              method,
              url,
              baseURL: '/',
              data: this.authClient,
            }).then(response => {
              this.$refs.createEditAuthClient.hide();
              this.loading = false;
              if (response.data.secret) {
                this.secret = response.data.secret
                this.$refs.secretModal.show();
              }
              else {
                this.$refs.authClientList.fetch();
              }
              ProcessMaker.alert(this.$t("The auth client was ") + verb + ".", this.$t("success"))
            }).catch(error => {
              this.disabled = false;
              this.errors = error.response.data.errors;
            });
          },
          resetValues() {
            this.title = this.$t('Create Auth-Client')
            this.secretTitle = this.$t('Copy Secret To Clipboard')
            this.authClient = {
              id: null,
              name: "",
              types: [],
              redirect: "",
              secret: "",
            };
            this.errors = {
              name: null,
              redirect: null,
              types: null
            };
            this.disabled = false;
            this.initCustomModalButtons();
          },
          edit(item) {
            this.title = this.$t('Edit Auth Client');
            this.authClient = item;
            this.$refs.createEditAuthClient.show();
          },
          initCustomModalButtons() {
            this.customModalButtons = [
              {
                content: "Close",
                action: "close",
                variant: "secondary",
                disabled: false,
                hidden: false,
              },
            ];
          },
          hideSecretModal() {
            this.$refs.secretModal.hide();
            this.$refs.authClientList.fetch();
          },
          copySecret(secret) {
            navigator.clipboard.writeText(secret).then(() => {
              ProcessMaker.alert(this.$t("Secret copied to clipboard."), "success");
            }, () => {
              ProcessMaker.alert(this.$t("Secret not copied to clipboard."), "danger");
            });
          },
        },
      })
    </script>
@endsection

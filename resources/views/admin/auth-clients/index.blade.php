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
            <div class="form-group" required>
                {!!Form::label('name', __('Name'))!!}
                {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'authClient.name',
                'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'])!!}
                <small class="form-text text-muted">{{ __('Name must be distinct') }}</small>
                <div class="invalid-feedback" v-if="errors.name">@{{ errors.name[0] }}</div>
            </div>
            <b-form-checkbox-group v-model="authClient.types">
              <div class="form-group">
                <div class="invalid-feedback d-block" v-if="errors.types">@{{ errors.types[0] }}</div>
                <b-form-checkbox value="authorization_code_grant">{{__('Enable Authorization Code Grant')}}</b-form-checkbox>
                <br />
                <template v-if="authClient['types'].includes('authorization_code_grant')">
                  {!!Form::label('redirect', __('Redirect URL'))!!}
                  {!!Form::text('redirect', null, ['class'=> 'form-control', 'v-model'=> 'authClient.redirect',
                  'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.redirect}','rows'=>3])!!}
                  <div class="invalid-feedback" v-if="errors.redirect">@{{ errors.redirect[0] }}</div>
                </template>
              </div>
              <div class="form-group">
                <b-form-checkbox value="password_client">{{__('Enable Password Grant')}}</b-form-checkbox>
              </div>
              <div class="form-group">
                <b-form-checkbox value="personal_access_client">{{__('Enable Personal Access Tokens')}}</b-form-checkbox>
              </div>
            </b-form-checkbox-group>
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
                <auth-clients-listing ref="authClientList" :filter="filter" @edit="edit"/>
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
        },
        beforeMount() {
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
              this.$refs.authClientList.fetch();
              this.loading = false;
              ProcessMaker.alert(this.$t("The auth client was ") + verb + ".", this.$t("success"))
            }).catch(error => {
              this.disabled = false;
              this.errors = error.response.data.errors;
            });
          },
          resetValues() {
            this.title = this.$t('Create Auth-Client')
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
          },
          edit(item) {
            this.title = this.$t('Edit Auth Client');
            this.authClient = item;
            this.$refs.createEditAuthClient.show();
          }
        },
      })
    </script>
@endsection

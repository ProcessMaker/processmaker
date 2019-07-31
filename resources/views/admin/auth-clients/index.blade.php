@extends('layouts.layout')

@section('title')
    {{__('Auth Clients')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Auth Clients') => null,
    ]])
    <div id="authClients">
        <div class="modal" role="dialog" ref="createEditAuthClient" id="createEditAuthClient">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{title}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                            {{__('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" @click.prevent="save" :disabled="disabled">
                            {{__('Save')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-3 page-content">
            <div class="row">
                <div class="col" align="right">
                    <button id="create_authclients" class="btn btn-secondary" type="button" data-toggle="modal"
                            data-target="#createEditAuthClient">
                        <i class="fas fa-plus"></i>
                        {{__('Auth Client')}}</a>
                    </button>
                </div>
            </div>
            <auth-clients-listing ref="authClientList" @edit="edit"/>
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
          errors: null,
          disabled: false,
          title:'',
        },
        beforeMount() {
          this.resetValues();
        },
        mounted() {
          $('#createEditAuthClient').on('hidden.bs.modal', () => {
            this.resetValues();
          });
        },
        methods: {
          save() {
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
              $('#createEditAuthClient').modal('hide');
              this.$refs.authClientList.fetch();
              this.loading = false;
              ProcessMaker.alert(this.$t("The auth client was ") + verb + ".", this.$t("success"))
            }).catch(error => {
              this.disabled = false;
              this.errors = error.response.data.errors;
              console.log("ERRORS set to ", this.errors)
            });
          },
          resetValues() {
            this.title = this.$t('Create An Auth-Client');
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
            $('#createEditAuthClient').modal('show');
          }
        },
      })
    </script>
@endsection

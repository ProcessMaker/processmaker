@extends('layouts.layout', ['title' => __('Data Sources')])

@section('title')
    {{__('Edit Data Source')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
       __('Designer') => route('processes.index'),
       __('Data Sources') => route('datasources.index'),
       __('Edit') . ' '  . $datasource['name'] => null,
   ]])
@endsection

@section('content')
    <div class="container" id="formDataSource">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-item nav-link active" id="nav-auth-tab" data-toggle="tab" href="#nav-auth"
                   role="tab" aria-controls="nav-auth" aria-selected="true">
                    {{ __('Details') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-item nav-link" id="nav-header-tab" data-toggle="tab" href="#nav-header"
                   role="tab" aria-controls="nav-header" aria-selected="true">
                    {{ __('Access Points') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-item nav-link" id="nav-test-tab" data-toggle="tab" href="#nav-test"
                   role="tab" aria-controls="nav-header" aria-selected="true">
                    {{ __('Test') }}
                </a>
            </li>
        </ul>

        <div class="container mt-3">
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-auth" role="tabpanel" aria-labelledby="nav-auth-tab">
                    <div class="row">
                        <div class="card card-body">

                            <div class="form-group">
                                {!! Form::label('name', __('Name')) !!}
                                {!! Form::text('name', null, ['id' => 'name', 'class'=> 'form-control', 'v-model'=> 'formData.name', 'rows' => 4, 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
                                <div class="invalid-feedback" v-if="errors.name">@{{errors.name[0]}}
                                </div>
                            </div>

                            <div class="form-group">
                                {!! Form::label('description', __('Description')) !!}
                                {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control', 'v-model'=> 'formData.description', 'rows' => 4, 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}']) !!}
                                <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}
                                </div>
                            </div>

                            <category-select
                                :label="$t('Category')"
                                :allow-empty="false"
                                api-get="datasource_categories"
                                api-list="datasource_categories"
                                v-model="formData.data_source_category_id"
                                :errors="errors.data_source_category_id">
                            </category-select>
                            <h5 class="card-title">{{ __('Authentication') }}</h5>

                            <div class="form-group">
                                {!! Form::label('auth', __('Method')) !!}
                                <multiselect
                                    v-model="selectedAuthType"
                                    :class="{'border border-danger':errors.authtype}"
                                    :options="authOptions"
                                    track-by="value"
                                    label="content"
                                    :allow-empty="false"
                                    :show-labels="false">
                                </multiselect>
                                <div class="invalid-feedback" :class="{'d-block': errors.authtype}" v-if="errors.authtype">@{{errors.authtype[0]}}</div>
                            </div>

                            <div class="form-group" v-show="formData.authtype === 'BEARER'">
                                {!! Form::label('token', __('Token')) !!}
                                {!! Form::textarea('token', null, ['id' => 'token', 'rows' => 4, 'class'=> 'form-control', 'v-model'=> 'credentials.token', 'placeholder' => empty($datasource->credentials['token']) ? '': '********' , 'rows' => 4, 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.token}']) !!}
                                <div class="invalid-feedback" v-if="errors.token">@{{errors.token[0]}}
                                </div>
                            </div>

                            <div class="form-group" v-show="formData.authtype === 'PASSWORD'">
                                {!! Form::label('url', __('Url Token')) !!}
                                {!! Form::text('url', null, ['id' => 'url', 'class'=> 'form-control', 'v-model'=> 'credentials.url', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.url}']) !!}
                                <div class="invalid-feedback" v-if="errors.url">@{{errors.url[0]}}
                                </div>
                            </div>

                            <div class="form-group" v-show="formData.authtype === 'PASSWORD'">
                                {!! Form::label('clientId', __('Client ID')) !!}
                                {!! Form::text('clientId', null, ['id' => 'clientId', 'class'=> 'form-control', 'v-model'=> 'credentials.clientId', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.clientId}']) !!}
                                <div class="invalid-feedback" v-if="errors.clientId">@{{errors.clientId[0]}}
                                </div>
                            </div>

                            <div class="form-group" v-show="formData.authtype === 'PASSWORD'">
                                {!! Form::label('clientSecret', __('Client Secret')) !!}
                                {!! Form::text('clientSecret', null, ['id' => 'clientSecret', 'class'=> 'form-control', 'v-model'=> 'credentials.clientSecret', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.clientSecret}']) !!}
                                <div class="invalid-feedback" v-if="errors.clientSecret">@{{errors.clientSecret[0]}}
                                </div>
                            </div>

                            <div class="form-group" v-show="formData.authtype === 'BASIC' || formData.authtype === 'PASSWORD'">
                                {!! Form::label('user', __('User')) !!}
                                {!! Form::text('user', null, ['id' => 'user', 'class'=> 'form-control', 'v-model'=> 'credentials.user', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.user}']) !!}
                                <div class="invalid-feedback" v-if="errors.user">@{{errors.user[0]}}
                                </div>
                            </div>

                            <div class="form-group" v-show="formData.authtype === 'BASIC' || formData.authtype === 'PASSWORD'">
                                {!! Form::label('password', __('Password')) !!}
                                {!! Form::text('password', null, ['id' => 'password', 'class'=> 'form-control', 'v-model'=> 'credentials.password', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.password}']) !!}
                                <div class="invalid-feedback" v-if="errors.password">@{{errors.password[0]}}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="tab-pane fade show" id="nav-header" role="tabpanel" aria-labelledby="nav-header-tab">
                    <div class="row">
                        <div class="card card-body">
                            <div class="row">
                                <div class="col">
                                </div>
                                <div class="col-8">
                                    <button type="button" href="#" @click="addEndpoint" id="add_endpoing"
                                            class="btn btn-secondary float-right">
                                        <i class="fas fa-plus"></i> {{__('Add')}}
                                    </button>
                                </div>
                            </div>

                            <end-point-list
                                ref="endpointsListing"
                                :endpoints="formData.endpoints"
                                :datasource="formData">
                            </end-point-list>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade show" id="nav-test" role="tabpanel" aria-labelledby="nav-header-tab">
                    <div class="row">
                        <div class="card card-body">
                            <test-end-points
                                ref="endpointsListing"
                                :endpoints="formData.endpoints"
                                :datasource="formData">
                            </test-end-points>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-body">
            <div class="col text-right mt-2">
                {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose'])!!}
                @can('create-datasources')
                    {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onSubmit'])!!}
                @endcan
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script src="{{mix('js/processes/datasources/edit.js')}}"></script>
    <script>
      const authorizations = [
        {
          "value": "NONE",
          "content": __("No Auth")
        },
        {
          "value": "BASIC",
          "content": __("Basic auth")
        },
        {
          "value": "BEARER",
          "content": __("Bearer Token")
        },
        {
          "value": "PASSWORD",
          "content": __("Password")
        }
      ];
      new Vue({
        el: "#formDataSource",
        data () {
          let formData = @json($datasource);
          formData.endpoints = formData.endpoints ? formData.endpoints : {};
          if (formData.endpoints instanceof Array) {
            const endpoints = {};
            formData.endpoints.forEach((item) => {
              endpoints[item.purpose] = item;
            });
            formData.endpoints = endpoints;
          }
          return {
            selectedAuthType: "",
            authOptions: authorizations,
            disabled: false,
            credentials: {
              token: "",
              user: "",
              password: "",
              clientId: "",
              clientSecret: "",
              url:''
            },
            errors: {},
            formData
          };
        },
        watch: {
          selectedAuthType: {
            handler (item) {
              if (item.value) {
                this.formData.authtype = item.value;
              }
            }
          },
          credentials: {
            deep: true,
            handler (data) {
              this.formData.credentials = data;
            }
          },
        },
        computed: {},
        methods: {
          onClose () {
            window.location = "/designer/datasources";
          },
          getMethod () {
            return this.formData.id ? "PUT" : "POST";
          },
          getUrl () {
            return this.formData.id ? "datasources/" + this.formData.id : "datasources";
          },
          onSubmit () {
            this.submitted = true;
            if (this.disabled) {
              return;
            }
            this.disabled = true;
            if (typeof this.formData.credentials !== "object") {
              delete this.formData.credentials;
            }
            ProcessMaker.apiClient({
              method: this.getMethod(),
              url: this.getUrl(),
              data: this.formData,
            })
              .then(response => {
                ProcessMaker.alert('{{__('The DataSource was saved.')}}', "success");
                this.onClose();
              })
              .catch(error => {
                this.errors = error.response.data.errors;
                this.disabled = false;
              });
          },
          addEndpoint () {
            const suggestions = [
              "list", "create", "update", "delete"
            ];
            let index = 0,
              purpose = suggestions[index],
              suffix = 0;
            while (this.$refs.endpointsListing.endpoints[purpose]) {
              index++;
              index >= suggestions.length ? (index = 0, suffix++) : null;
              purpose = suggestions[index] + (suffix ? "_" + suffix : "");
            }
            let endpoint = {
              id: this.$refs.endpointsListing.endpoints.length,
              view: false,
              purpose,
              method: "",
              url: "",
              headers: [],
              body_type: "",
              body: ""
            };
            this.$refs.endpointsListing.endpoints[purpose] = endpoint;
            this.$refs.endpointsListing.fetch();
            this.$refs.endpointsListing.detail(endpoint);
          },
        },
        mounted () {
          this.selectedAuthType = this.authOptions.filter(item => {
            if (item.value === this.formData.authtype) {
              return item;
            }
          });
        },
      });
    </script>
@endsection

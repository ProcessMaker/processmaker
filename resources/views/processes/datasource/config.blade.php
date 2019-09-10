@extends('layouts.layout')

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
    <div class="container" id="configDatasource">
        <div class="row">
            <div class="col">
                <div class="card card-body">
                    {!! Form::open() !!}
                    <div class="form-group">
                        {!! Form::label('name', __('Name')) !!}
                        {!! Form::text('name', null, ['id' => 'title','class'=> 'form-control', 'v-model' => 'formData.name',
                        'v-bind:class' => '{"form-control":true, "is-invalid":errors.name}']) !!}
                        <small class="form-text text-muted" v-if="! errors.name">
                            {{ __('The datasource name must be distinct.') }}
                        </small>
                        <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('description', __('Description')) !!}
                        {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control',
                        'v-model' => 'formData.description', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.description}']) !!}
                        <div class="invalid-feedback" v-for="description in errors.description">@{{description}}
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('type', __('Authentication Type')) !!}
                        <multiselect
                                :class="{'border border-danger':errors.authtype}"
                                v-model="selectedAuthType"
                                :placeholder="$t('Select Authentication Type')"
                                :options="authtypeOptions"
                                track-by="value"
                                label="content"
                                :allow-empty="false"
                                :show-labels="false">
                        </multiselect>
                        <div class="invalid-feedback" v-for="type in errors.authtype">@{{authtype}}</div>
                    </div>
                    <category-select
                            :label="$t('Category')"
                            api-get="datasource_categories"
                            api-list="datasource_categories"
                            v-model="formData.data_source_category_id"
                            :errors="errors.data_source_category_id">
                    </category-select>
                    <br>
                    <div class="text-right">
                        {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                        {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/datasources/config.js')}}"></script>
    <script>
      new Vue({
        el: '#configDatasource',
        data() {
          return {
            formData: @json($datasource),
            selectedAuthType: '',
            authtypeOptions: [
              {
                'value': 'NONE',
                'content': __('No Auth')
              },
              {
                'value': 'BASIC',
                'content': __('Basic auth')
              },
              {
                'value': 'BEARER',
                'content': __('Bearer Token')
              }
            ],
            errors: {
              'name': null,
              'description': null,
              'category': null,
            },
            disabled: false,
          }
        },
        watch: {
          selectedAuthType: {
            handler(item) {
              if (item.value) {
                this.formData.authtype = item.value;
              }
            }
          }
        },
        mounted() {
          this.resetErrors();
          this.selectedAuthType = this.authtypeOptions.filter(item => {
            if (item.value === this.formData.authtype) {
              return item;
            }
          })
        },
        methods: {
          resetErrors() {
            this.errors = Object.assign({}, {
              name: null,
              description: null,
            });
          },
          onClose() {
            window.location = '/designer/datasources';
          },
          onUpdate() {
            this.resetErrors();
            //single click
            if (this.disabled) {
              return
            }
            this.disabled = true;
            ProcessMaker.apiClient
              .put('datasources/' + this.formData.id, this.formData)
              .then(response => {
                ProcessMaker.alert('{{ __('The datasource was updated.') }}', 'success');
                this.onClose();
              })
              .catch(error => {
                this.disabled = false;
                if (error.response.status && error.response.status === 422) {
                  this.errors = error.response.data.errors;
                }
              });
          }
        }
      });
    </script>
@endsection
@extends('layouts.layout')

@section('title')
    {{__('Data Sources')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Data Sources') => null,
    ]])
@endsection
@section('content')
    <div class="px-3 page-content" id="datasourceIndex">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-item nav-link active" id="nav-sources-tab" data-toggle="tab" href="#nav-sources"
                   role="tab" aria-controls="nav-sources" aria-selected="true">
                    {{ __('Sources') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-item nav-link" id="nav-categories-tab" data-toggle="tab" href="#nav-categories"
                   role="tab" aria-controls="nav-categories" aria-selected="true">
                    {{ __('Categories') }}
                </a>
            </li>
        </ul>

        <div class="mt-3">
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-sources" role="tabpanel" aria-labelledby="nav-sources-tab">
                    <div class="card card-body">
                        <div class="row">
                            <div class="col">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                      <span class="input-group-text">
                      <i class="fas fa-search"></i>
                      </span>
                                    </div>
                                    <input v-model="filter" class="form-control" placeholder="{{ __('Search') }}...">
                                </div>
                            </div>
                            <div class="col-8 ">
                                @can('create-datasources')
                                    <button type="button" href="#" id="create_datasource" class="btn btn-secondary float-right"
                                            data-toggle="modal"
                                            data-target="#createDatasource">
                                        <i class="fas fa-plus"></i> {{__('Data Source')}}
                                    </button>
                                @endcan
                                    <button type="button" id="create_category" class="btn btn-secondary float-right" data-toggle="modal"
                                            data-target="#createCategory">
                                        <i class="fas fa-plus"></i> {{ __('Category') }}
                                    </button>
                            </div>
                        </div>
                        <datasource-list
                                ref="datasourceListing"
                                :filter="filter"
                                :permission="{{ \Auth::user()->hasPermissionsFor('datasources') }}"
                                v-on:reload="reload">
                        </datasource-list>
                    </div>
                </div>
                <div class="tab-pane fade show" id="nav-categories" role="tabpanel" aria-labelledby="nav-categories-tab">
                    <div class="card card-body">
                        <div class="row">
                            <div class="col">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                      <span class="input-group-text">
                      <i class="fas fa-search"></i>
                      </span>
                                    </div>
                                    <input v-model="filter" class="form-control" placeholder="{{ __('Search') }}...">
                                </div>
                            </div>
                            <div class="col-8">
                                @can('create-category')
                                    <button type="button" id="create_category" class="btn btn-secondary float-right" data-toggle="modal"
                                            data-target="#createCategory">
                                        <i class="fas fa-plus"></i> {{ __('Category') }}
                                    </button>
                                @endcan

                            </div>
                        </div>
                        <categories-listing
                                ref="list"
                                filter="filter"
                                api-route="{{$route}}"
                                :permission="{{$permissions}}"
                                location="{{$location}}"
                                include="{{$include}}"
                                label-count="{{$labelCount}}"
                                count="{{$count}}">
                        </categories-listing>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @can('create-category')
        <div class="modal fade" id="createCategory" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('Create Datasource Category')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="onClose">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            {!!Form::label('name', __('Category Name'))!!}
                            {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'name',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'])!!}
                            <small class="form-text text-muted" v-if="! errors.name">
                                {{ __('The category name must be distinct.') }}
                            </small>
                            <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('status', __('Status')) !!}
                            {!! Form::select('status', ['ACTIVE' => __('active'), 'INACTIVE' => __('inactive')], null, ['id' => 'status',
                            'class' => 'form-control', 'v-model' => 'status', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.status}']) !!}
                            <div class="invalid-feedback" v-for="status in errors.status">@{{status}}</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" @click="onClose">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" @click="onSubmit" :disabled="disabled">
                            {{ __('Save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

    @endcan

    @can('create-datasources')
        <div class="modal fade" id="createDatasource" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('Create Datasource')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="onClose">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    @if ($datasourceCategories !== 0)
                        <div class="modal-body">
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
                                    :errors="errors.datasource_category_id">
                            </category-select>
                        </div>
                    @else
                        <div class="modal-body">
                            <div>{{__('Categories are required to create a datasource')}}</div>
                            <a href="{{ url('designer/datasources/categories') }}"
                               class="btn btn-primary container mt-2">
                                {{__('Add Category')}}
                            </a>
                        </div>
                    @endif
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" @click="onClose">
                            {{__('Cancel')}}
                        </button>
                        @if ($datasourceCategories !== 0)
                            <button type="button" @click="onSubmit" class="btn btn-secondary ml-2" :disabled="disabled">
                                {{__('Save')}}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endcan


@endsection

@section('js')
    <script src="{{mix('js/processes/datasources/index.js')}}"></script>

    @can('create-datasources')
        <script>
          new Vue({
            el: '#createDatasource',
            data() {
              return {
                formData: {},
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
                  this.formData.authtype = item.value;
                }
              }
            },
            mounted() {
              this.resetFormData();
              this.resetErrors();
            },
            methods: {
              resetFormData() {
                this.formData = Object.assign({}, {
                  name: null,
                  description: null,
                });
              },
              resetErrors() {
                this.errors = Object.assign({}, {
                  name: null,
                  description: null,
                });
              },
              onClose() {
                this.resetFormData();
                this.resetErrors();
              },
              onSubmit() {
                this.resetErrors();
                //single click
                if (this.disabled) {
                  return
                }
                this.disabled = true;
                ProcessMaker.apiClient
                  .post('datasources', this.formData)
                  .then(response => {
                    ProcessMaker.alert('{{ __('The datasource was created.') }}', 'success');
                    window.location = '/designer/datasources/' + response.data.id + '/edit';
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
    @endcan

    @can('create-category')
        <script>
            new Vue({
                el: '#createCategory',
                data: {
                    errors: {},
                    name: '',
                    status: 'ACTIVE',
                    disabled: false,
                    route: @json($route),
                    location: @json($location),
                },
                methods: {
                    onClose() {
                        this.name = '';
                        this.status = 'ACTIVE';
                        this.errors = {};
                    },
                    onSubmit() {
                        this.errors = {};
                        //single click
                        if (this.disabled) {
                            return
                        }
                        this.disabled = true;
                        ProcessMaker.apiClient.post(this.route, {
                            name: this.name,
                            status: this.status
                        })
                            .then(response => {
                                ProcessMaker.alert('{{__('The category was created.')}}', 'success');
                                window.location = this.location;
                            })
                            .catch(error => {
                                this.disabled = false;
                                if (error.response.status === 422) {
                                    this.errors = error.response.data.errors
                                }
                            });
                    }
                }
            })
        </script>
    @endcan


@endsection

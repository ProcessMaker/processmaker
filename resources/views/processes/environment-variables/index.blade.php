@extends('layouts.layout')

@section('title')
    {{__('Environment Variables')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Environment Variables') => null,
    ]])
@endsection
@section('content')
    <div class="px-3 page-content" id="process-variables-listing">
        <div id="search-bar" class="search mb-3" vcloak>
            <div class="d-flex flex-column flex-md-row">
                <div class="flex-grow-1">
                    <div id="search" class="mb-3 mb-md-0">
                        <div class="input-group w-100">
                            <input v-model="filter" class="form-control" placeholder="{{__('Search')}}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" data-original-title="Search"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                @can('create-environment_variables')
                    <div class="d-flex ml-md-2 flex-column flex-md-row">
                        <button type="button" id="create_envvar" class="btn btn-secondary" data-toggle="modal"
                                data-target="#createEnvironmentVariable">
                            <i class="fas fa-plus"></i> {{__('Environment Variable')}}
                        </button>
                    </div>
                @endcan
            </div>
        </div>
        <variables-listing ref="listVariable" :filter="filter"
                           :permission="{{ \Auth::user()->hasPermissionsFor('environment_variables') }}"
                           @delete="deleteVariable"></variables-listing>
    </div>

    @can('create-environment_variables')
        <div class="modal" tabindex="-1" role="dialog" id="createEnvironmentVariable" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('Create Environment Variable')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="onClose">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            {!!Form::label('name', __('Name'))!!}
                            {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'name',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'])!!}
                            <small class="form-text text-muted"
                                   v-if="! errors.name">{{ __('The environment variable name must be distinct.') }}</small>
                            <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
                        </div>
                        <div class="form-group">
                            {!!Form::label('description', __('Description'))!!}
                            {!!Form::textArea('description', null, ['class'=> 'form-control', 'v-model'=> 'description',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}','rows'=>3])!!}
                            <div class="invalid-feedback" v-for="description in errors.description">@{{description}}
                            </div>
                        </div>
                        <div class="form-group">
                            {!!Form::label('value', __('Value'))!!}
                            {!!Form::text('value', null, ['class'=> 'form-control', 'v-model'=> 'value',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.value}'])!!}
                            <div class="invalid-feedback" v-for="value in errors.value">@{{value}}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" @click="onClose">
                            {{__('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-secondary" @click="onSubmit" :disabled="disabled">
                            {{__('Save')}}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endcan
@endsection

@section('js')
    <script src="{{mix('js/processes/environment-variables/index.js')}}"></script>

    @can('create-environment_variables')
        <script>
          new Vue({
            el: '#createEnvironmentVariable',
            data: {
              errors: {},
              name: '',
              description: '',
              value: '',
              disabled: false,
            },
            methods: {
              onClose() {
                this.name = '';
                this.description = '';
                this.value = '';
                this.errors = {};
              },
              onSubmit() {
                this.errors = {};
                //single click
                if (this.disabled) {
                  return
                }
                this.disabled = true;
                ProcessMaker.apiClient.post('environment_variables', {
                  name: this.name,
                  description: this.description,
                  value: this.value
                })
                  .then(response => {
                    ProcessMaker.alert('{{__('The environment variable was created.')}}', 'success');
                    window.location = '/designer/environment-variables';
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

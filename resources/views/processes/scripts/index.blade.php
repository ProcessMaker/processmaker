@extends('layouts.layout')
@section('title')
    {{__('Scripts')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Processes') => route('processes.index'),
        __('Scripts') => null,
    ]])
    <div class="container page-content" id="scriptIndex">
        <div class="row">
            <div class="col">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                        <i class="fas fa-search"></i>
                        </span>
                    </div>
                    <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                </div>
            </div>

            <div class="col-8" align="right">
                @can('create-scripts')
                    <a href="#" class="btn btn-secondary" data-toggle="modal" data-target="#addScript"><i
                                class="fas fa-plus"></i>
                        {{__('Script')}}</a>
                @endcan
            </div>
        </div>
        <div class="container-fluid">
            <script-listing :filter="filter"
                            :script-formats='@json($scriptFormats)'
                            :permission="{{ \Auth::user()->hasPermissionsFor('scripts') }}"
                            ref="listScript"
                            @delete="deleteScript">
            </script-listing>
        </div>
    </div>

    @can('create-scripts')
        <div class="modal" tabindex="-1" role="dialog" id="addScript">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('Create Script')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="onClose">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            {!!Form::label('title', __('Name'))!!}
                            {!!Form::text('title', null, ['class'=> 'form-control', 'v-model'=> 'title', 'v-bind:class' =>
                            '{\'form-control\':true, \'is-invalid\':addError.title}'])!!}
                            <small class="form-text text-muted"
                                   v-if="! addError.title">{{ __('The script name must be distinct.') }}</small>
                            <div class="invalid-feedback" v-for="title in addError.title">@{{title}}</div>
                        </div>
                        <div class="form-group">
                            {!!Form::label('description', __('Description'))!!}
                            {!!Form::textarea('description', null, ['rows'=>'2','class'=> 'form-control', 'v-model'=> 'description',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.description}'])!!}
                            <div class="invalid-feedback" v-for="description in addError.description">@{{description}}
                            </div>
                        </div>
                        <div class="form-group">
                            {!!Form::label('language', __('Language'))!!}
                            {!!Form::select('language', [''=>__('Select')] + $scriptFormats, null, ['class'=>
                            'form-control', 'v-model'=> 'language', 'v-bind:class' => '{\'form-control\':true,
                            \'is-invalid\':addError.language}']);!!}
                            <div class="invalid-feedback" v-for="language in addError.language">@{{language}}</div>
                        </div>

                        <div class="form-group">
                            <label class="typo__label">{{__('Run script as')}}</label>
                            <multiselect v-model="selectedUser" label="fullname" :options="users"
                                         :searchable="true"></multiselect>
                        <small class="form-text text-muted">{{__('Select a user to set the api access of the script')}}</small>
                        </div>

                        <div class="form-group">
                            {!! Form::label('timeout', __('Timeout')) !!}
                            <div class="form-row ml-0">
                                {!! Form::text('timeout', null, ['id' => 'timeout', 'class'=> 'form-control col-2',
                                'v-model' => 'timeout', 'pattern' => '[0-9]*', 'v-bind:class' => '{"form-control":true, "is-invalid":addError.timeout}']) !!}
                                {!! Form::range(null, null, ['id' => 'timeout-range', 'class'=> 'custom-range col ml-1 mt-2',
                                'v-model' => 'timeout', 'min' => 0, 'max' => 300]) !!}
                                <div class="invalid-feedback" v-for="timeout in addError.timeout">@{{timeout}}</div>
                            </div>
                            <small class="form-text text-muted" v-if="! addError.timeout">
                                {{ __('How many seconds the script should be allowed to run (0 is unlimited).') }}
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" @click="onClose">
                            {{__('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" @click="onSubmit" :disabled="disabled">
                            {{__('Save')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('js')
    <script src="{{mix('js/processes/scripts/index.js')}}"></script>

    @can('create-scripts')
        <script>
          new Vue({
            el: '#addScript',
            data: {
              title: '',
              language: '',
              description: '',
              code: '',
              addError: {},
              selectedUser: '',
              users:@json($users),
              timeout: 60,
              disabled: false,
            },
            methods: {
              onClose() {
                this.title = '';
                this.language = '';
                this.description = '';
                this.code = '';
                this.timeout = 60;
                this.addError = {};
              },
              onSubmit() {
                this.errors = Object.assign({}, {
                  name: null,
                  description: null,
                  status: null
                });
                //single click
                if (this.disabled) {
                  return
                }
                this.disabled = true;
                ProcessMaker.apiClient.post("/scripts", {
                  title: this.title,
                  language: this.language,
                  description: this.description,
                  run_as_user_id: this.selectedUser.id,
                  code: "[]",
                  timeout: this.timeout
                })
                  .then(response => {
                    ProcessMaker.alert('{{__('The script was created.')}}', 'success');
                    window.location = "/processes/scripts/" + response.data.id + "/builder";
                  })
                  .catch(error => {
                    this.disabled = false;
                    if (error.response.status && error.response.status === 422) {
                      if (error.response.data.errors.run_as_user_id !== undefined) {
                        ProcessMaker.alert(error.response.data.errors.run_as_user_id[0], 'danger');
                      }
                      this.addError = error.response.data.errors;
                    }
                  })
              }
            }
          })
        </script>
    @endcan
@endsection

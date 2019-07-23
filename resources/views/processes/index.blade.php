@extends('layouts.layout')

@section('title')
    @php
        $title = __('Processes');
        $status = request()->get('status');
        if( $status === 'inactive'){
            $title = __('Archived Processes');
        }
    @endphp
    {{$title}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Processes') => route('processes.index'),
        $title => null,
    ]])
    <div class="px-3 page-content" id="processIndex">
        <div class="row">
            <div class="col">
                <div class="input-group">
                    <div class="input-group-prepend">
					<span class="input-group-text" id="basic-addon1">
						<i class="fas fa-search"></i>
					</span>
                    </div>
                    <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                </div>

            </div>
            <div class="col-8" align="right">
                @can('import-processes')
                    <a href="#" id="import_process" class="btn btn-outline-secondary" @click="goToImport"><i class="fas fa-file-import"></i>
                        {{__('Import')}}</a>
                @endcan
                @can('create-processes')
                    <a href="#" id="create_process" class="btn btn-secondary" data-toggle="modal" data-target="#addProcess"><i
                                class="fas fa-plus"></i>
                        {{__('Process')}}</a>
                @endcan
            </div>
        </div>
        <div class="container-fluid">
            <processes-listing
                    ref="processListing"
                    :filter="filter"
                    status="{{ $status }}"
                    v-on:edit="edit"
                    v-on:reload="reload"
                    :permission="{{ \Auth::user()->hasPermissionsFor('processes') }}"
            ></processes-listing>
        </div>
    </div>

    @can('create-processes')
        <div class="modal" tabindex="-1" role="dialog" id="addProcess">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('Create Process')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="onClose">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    @if (count($processCategories) > 1)
                        <div class="modal-body">
                            <div class="form-group">
                                {!! Form::label('name', __('Name')) !!}
                                {!! Form::text('name', null, [
                                'autocomplete' => 'off',
                                'class'=> 'form-control',
                                'v-model'=> 'name',
                                'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.name}']) !!}
                                <small class="form-text text-muted"
                                       v-if="! addError.name">{{ __('The process name must be distinct.') }}</small>
                                <div class="invalid-feedback" v-for="name in addError.name">@{{name}}</div>
                            </div>
                            <div class="form-group">
                                {!! Form::label('description', __('Description')) !!}
                                {!! Form::textarea('description', null, [
                                'class'=> 'form-control',
                                'rows' => '3',
                                'v-model'=> 'description',
                                'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.description}']) !!}
                                <div class="invalid-feedback" v-for="description in addError.description">
                                    @{{description}}
                                </div>
                            </div>
                            <div class="form-group">
                                {!! Form::label('process_category_id', __('Category'))!!}
                                {!! Form::select('process_category_id', [null => __('Select')] + $processCategories, null, [
                                'class'=> 'form-control',
                                'v-model'=> 'process_category_id',
                                'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.process_category_id}']) !!}
                                <div class="invalid-feedback" v-for="category in addError.process_category_id">
                                    @{{category}}
                                </div>
                            </div>
                            <div class="form-group">
                                {!! Form::label('fileName', __('Upload BPMN File (optional)')) !!}
                                <div class="input-group">
                                    <input type="text" name="fileName" class="form-control" v-model="selectedFile">
                                    <button @click="browse" class="btn btn-secondary"><i class="fas fa-upload"></i>
                                        {{__('Upload file')}}
                                    </button>
                                    <input type="file" class="custom-file-input" :class="{'is-invalid': addError.bpmn && addError.bpmn.length}" ref="customFile" @change="onFileChange" accept=".bpmn" style="height: 1em;">
                                    <div class="invalid-feedback" v-for="error in addError.bpmn">@{{error}}</div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="modal-body">
                            <div>{{__('Categories are required to create a process')}}</div>
                            <a href="{{ url('designer/categories') }}" class="btn btn-primary container mt-2">
                                {{__('Add Category')}}
                            </a>
                        </div>
                    @endif
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal"
                                @click="onClose">{{__('Cancel')}}</button>
                        @if (count($processCategories) > 1)
                            <button type="button" class="btn btn-secondary ml-2" @click="onSubmit" :disabled="disabled">
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

    @can('create-processes')
        <script>
          new Vue({
            el: '#addProcess',
            data: {
              name: '',
              selectedFile: '',
              categoryOptions: '',
              description: '',
              process_category_id: '',
              addError: {},
              status: '',
              bpmn: '',
              processCategories: @json($processCategories),
              disabled: false
            },
            methods: {
              browse() {
                this.$refs.customFile.click();
              },
              onFileChange(e) {
                let files = e.target.files || e.dataTransfer.files;

                if (!files.length) {
                  return;
                }

                this.selectedFile = files[0].name;
                this.file = this.$refs.customFile.files[0];
              },
              onClose() {
                this.name = '';
                this.description = '';
                this.process_category_id = '';
                this.status = '';
                this.addError = {};
              },
              onSubmit() {
                this.errors = Object.assign({}, {
                  name: null,
                  description: null,
                  process_category_id: null,
                  status: null
                });
                if (this.process_category_id === '') {
                  this.addError = {"process_category_id": ["{{__('The category field is required.')}}"]};
                  return;
                }
                //single click
                if (this.disabled) {
                  return;
                }
                this.disabled = true;

                let formData = new FormData();
                formData.append('name', this.name);
                formData.append('description', this.description);
                formData.append('process_category_id', this.process_category_id);
                if (this.file) {
                  formData.append('file', this.file);
                }

                ProcessMaker.apiClient.post("/processes", formData,
                  {
                    headers: {
                      'Content-Type': 'multipart/form-data'
                    }
                  })
                  .then(response => {
                    ProcessMaker.alert('{{__('The process was created.')}}', 'success')
                    window.location = "/modeler/" + response.data.id
                  })
                  .catch(error => {
                    this.disabled = false;
                    this.addError = error.response.data.errors;
                  })
              }
            }
          })
        </script>
    @endcan

    <script src="{{mix('js/processes/index.js')}}"></script>
@endsection

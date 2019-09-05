@extends('layouts.layout')

@section('title')
    {{__('Screens')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Screens') => null,
    ]])
@endsection
@section('content')
    <div class="px-3 page-content" id="screenIndex">
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
                @can('import-screen')
                    <a href="#" class="btn btn-outline-secondary" @click="goToImport"><i class="fas fa-file-import"></i>
                        {{__('Import')}}</a>
                @endcan
                @can('create-screens')
                    <button type="button" href="#" id="create_screen" class="btn btn-secondary" data-toggle="modal"
                            data-target="#createScreen">
                        <i class="fas fa-plus"></i> {{__('Screen')}}
                    </button>
                @endcan
            </div>
        </div>

        <screen-listing ref="screenListing"
                        :filter="filter"
                        :permission="{{ \Auth::user()->hasPermissionsFor('screens') }}"
                        v-on:reload="reload">
        </screen-listing>
    </div>

    @can('create-screens')
        <div class="modal fade" id="createScreen" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('Create Screen')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="onClose">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    @if ($screenCategories !== 0)
                        <div class="modal-body">
                            <div class="form-group">
                                {!! Form::label('title', __('Name')) !!}
                                {!! Form::text('title', null, ['id' => 'title','class'=> 'form-control', 'v-model' => 'formData.title',
                                'v-bind:class' => '{"form-control":true, "is-invalid":errors.title}']) !!}
                                <small class="form-text text-muted" v-if="! errors.title">
                                    {{ __('The screen name must be distinct.') }}
                                </small>
                                <div class="invalid-feedback" v-for="title in errors.title">@{{title}}</div>
                            </div>
                            <div class="form-group">
                                {!! Form::label('type', __('Type')) !!}
                                {!! Form::select('type', [null => __('Select')] + $types, '', ['id' => 'type','class'=> 'form-control', 'v-model' => 'formData.type',
                                'v-bind:class' => '{"form-control":true, "is-invalid":errors.type}']) !!}
                                <div class="invalid-feedback" v-for="type in errors.type">@{{type}}</div>
                            </div>
                            <div class="form-group">
                                {!! Form::label('description', __('Description')) !!}
                                {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control',
                                'v-model' => 'formData.description', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.description}']) !!}
                                <div class="invalid-feedback" v-for="description in errors.description">@{{description}}
                                </div>
                            </div>
                            <category-select :label="$t('Category')" api-get="screen_categories" api-list="screen_categories" v-model="formData.screen_category_id" :errors="errors.screen_category_id">
                            </category-select>
                        </div>
                    @else
                        <div class="modal-body">
                            <div>{{__('Categories are required to create a screen')}}</div>
                            <a href="{{ url('designer/screens/categories') }}" class="btn btn-primary container mt-2">
                                {{__('Add Category')}}
                            </a>
                        </div>
                    @endif
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" @click="onClose">
                            {{__('Cancel')}}
                        </button>
                        @if ($screenCategories !== 0)
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
    <script src="{{mix('js/processes/screens/index.js')}}"></script>

    @can('create-screens')
        <script>
          new Vue({
            el: '#createScreen',
            data() {
              return {
                formData: {},
                errors: {
                  'title': null,
                  'type': null,
                  'description': null,
                  'category': null,
                },
                disabled: false,
              }
            },
            mounted() {
              this.resetFormData();
              this.resetErrors();
            },
            methods: {
              resetFormData() {
                this.formData = Object.assign({}, {
                  title: null,
                  type: '',
                  description: null,
                });
              },
              resetErrors() {
                this.errors = Object.assign({}, {
                  title: null,
                  type: null,
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
                ProcessMaker.apiClient.post('screens', this.formData)
                  .then(response => {
                    ProcessMaker.alert('{{__('The screen was created.')}}', 'success');
                    window.location = '/designer/screen-builder/' + response.data.id + '/edit';
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
@endsection

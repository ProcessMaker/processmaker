    <div class="page-content mb-0" id="screenIndex">
        <div id="search-bar" class="search" vcloak>
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
                @canany(['import-screens', 'create-screens'])
                    <div class="d-flex ml-md-0 flex-column flex-md-row">
                        @can('import-screens')
                            <div class="mb-3 mb-md-0 ml-md-2">
                                <a href="#" class="btn btn-outline-secondary w-100" @click="goToImport">
                                    <i class="fas fa-file-import"></i> {{__('Import')}}
                                </a>
                            </div>
                        @endcan
                        @can('create-screens')
                            <div class="mb-3 mb-md-0 ml-md-2">
                                <button type="button" href="#" id="create_screen" class="btn btn-secondary w-100" data-toggle="modal"
                                        data-target="#createScreen">
                                    <i class="fas fa-plus"></i> {{__('Screen')}}
                                </button>
                            </div>
                        @endcan
                    </div>
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
                    @if ($config->countCategories !== 0)
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
                                {!! Form::select('type', [null => __('Select')] + $config->types, '', ['id' => 'type','class'=> 'form-control', 'v-model' => 'formData.type',
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
                        @if ($config->countCategories !== 0)
                            <button type="button" @click="onSubmit" class="btn btn-secondary ml-2" :disabled="disabled">
                                {{__('Save')}}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endcan

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
@append

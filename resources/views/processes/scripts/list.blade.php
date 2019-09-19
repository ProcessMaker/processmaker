    <div class="px-3 page-content" id="scriptIndex">
        <div id="search-bar" class="search mt-2 bg-light p-2" vcloak>
            <div class="d-flex">
                <div class="flex-grow-1">
                    <div id="search" class="pr-2">
                        <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <button title="" type="button" class="btn btn-primary" data-original-title="Search"><i class="fas fa-search"></i></button>
                    @can('create-scripts')
                        <a href="#" id="create_script" class="btn btn-secondary" data-toggle="modal"
                           data-target="#addScript"><i
                                    class="fas fa-plus"></i>
                            {{__('Script')}}</a>
                    @endcan
                </div>
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
                    @if ($countCategories !== 0)
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
                                <div class="invalid-feedback" v-for="description in addError.description">
                                    @{{description}}
                                </div>
                            </div>
                            <category-select :label="$t('Category')" api-get="script_categories"
                                             api-list="script_categories" v-model="script_category_id"
                                             :errors="addError.script_category_id">
                            </category-select>
                            <div class="form-group">
                                {!!Form::label('language', __('Language'))!!}
                                {!!Form::select('language', [''=>__('Select')] + $scriptFormats, null, ['class'=>
                                'form-control', 'v-model'=> 'language', 'v-bind:class' => '{\'form-control\':true,
                                \'is-invalid\':addError.language}']);!!}
                                <div class="invalid-feedback" v-for="language in addError.language">@{{language}}</div>
                            </div>

                            <div class="form-group">
                                <label class="typo__label">{{__('Run script as')}}</label>
                                <select-user v-model="selectedUser" :multiple="false"
                                             :class="{'is-invalid': addError.run_as_user_id}">
                                </select-user>
                                <small class="form-text text-muted"
                                       v-if="! addError.run_as_user_id">{{__('Select a user to set the API access of the Script')}}</small>
                                <div class="invalid-feedback" v-for="run_as_user_id in addError.run_as_user_id">
                                    @{{run_as_user_id}}
                                </div>
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
                                    {{ __('Enter how many seconds the Script runs before timing out (0 is unlimited).') }}
                                </small>
                            </div>
                        </div>
                    @else
                        <div class="modal-body">
                            <div>{{__('Categories are required to create a script')}}</div>
                            <a href="{{ url('designer/scripts/categories') }}" class="btn btn-primary container mt-2">
                                {{__('Add Category')}}
                            </a>
                        </div>
                    @endif
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" @click="onClose">
                            {{__('Cancel')}}
                        </button>
                        @if ($countCategories !== 0)
                            <button type="button" class="btn btn-secondary ml-2" @click="onSubmit" :disabled="disabled">
                                {{__('Save')}}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endcan

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
              script_category_id: '',
              code: '',
              addError: {},
              selectedUser: '',
              users: [],
              timeout: 60,
              disabled: false,
            },
            methods: {
              onClose() {
                this.title = '';
                this.language = '';
                this.description = '';
                this.script_category_id = '';
                this.code = '';
                this.timeout = 60;
                this.addError = {};
              },
              onSubmit() {
                this.errors = Object.assign({}, {
                  name: null,
                  description: null,
                  status: null,
                  script_category_id: null
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
                  script_category_id: this.script_category_id,
                  run_as_user_id: this.selectedUser ? this.selectedUser.id : null,
                  code: "[]",
                  timeout: this.timeout
                })
                  .then(response => {
                    ProcessMaker.alert('{{__('The script was created.')}}', 'success');
                    window.location = "/designer/scripts/" + response.data.id + "/builder";
                  })
                  .catch(error => {
                    this.disabled = false;
                    if (error.response.status && error.response.status === 422) {
                      this.addError = error.response.data.errors;
                    }
                  })
              }
            }
          })
        </script>
    @endcan
@append

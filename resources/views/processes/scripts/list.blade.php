    <div class="page-content mb-0" id="scriptIndex">
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
                @can('create-scripts')
                    <div class="d-flex ml-md-2 flex-column flex-md-row">
                        <a href="#" id="create_script" class="btn btn-secondary" data-toggle="modal"
                           data-target="#addScript"><i
                                    class="fas fa-plus"></i>
                            {{__('Script')}}</a>
                    </div>
                @endcan
            </div>
        </div>

        <div class="container-fluid">
            <script-listing :filter="filter"
                            :script-executors='@json($config->scriptExecutors)'
                            :permission="{{ \Auth::user()->hasPermissionsFor('scripts') }}"
                            ref="listScript"
                            @delete="deleteScript">
            </script-listing>
        </div>
    </div>

    @can('create-scripts')
        <div class="modal" tabindex="-1" role="dialog" id="addScript" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('Create Script')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="onClose">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    @if ($config->countCategories !== 0)
                        <div class="modal-body">
                            <div class="form-group">
                                {!!Form::label('title', __('Name'))!!}<small class="ml-1">*</small>
                                {!!Form::text('title', null, ['class'=> 'form-control', 'v-model'=> 'title', 'v-bind:class' =>
                                '{\'form-control\':true, \'is-invalid\':addError.title}'])!!}
                                <small class="form-text text-muted"
                                       v-if="! addError.title">{{ __('The script name must be distinct.') }}</small>
                                <div class="invalid-feedback" v-for="title in addError.title">@{{title}}</div>
                            </div>
                            <div class="form-group">
                                {!!Form::label('description', __('Description'))!!}<small class="ml-1">*</small>
                                {!!Form::textarea('description', null, ['rows'=>'2','class'=> 'form-control', 'v-model'=> 'description',
                                'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.description}'])!!}
                                <div class="invalid-feedback" v-for="description in addError.description">
                                    @{{description}}
                                </div>
                            </div>
                            <category-select :label="$t('Category')" api-get="script_categories"
                                             api-list="script_categories" v-model="script_category_id"
                                             :errors="addError.script_category_id" ref="categorySelect">
                            </category-select>
                            <div class="form-group">
                                {!!Form::label('script_executor_id', __('Language'))!!}<small class="ml-1">*</small>
                                {!!Form::select('script_executor_id', [''=>__('Select')] + $config->scriptExecutors, null, ['class'=>
                                'form-control', 'v-model'=> 'script_executor_id', 'v-bind:class' => '{\'form-control\':true,
                                \'is-invalid\':addError.script_executor_id}']);!!}
                                <div class="invalid-feedback" v-for="error in addError.script_executor_id">@{{error}}</div>
                            </div>

                            <div class="form-group">
                                <label class="typo__label">{{__('Run script as')}}<small class="ml-1">*</small></label>
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
                            <component
                                v-for="(cmp,index) in createScriptHooks"
                                :key="`create-script-hook-${index}`"
                                :is="cmp"
                                :script="script"
                                ref="createScriptHooks"
                            ></component>
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
                        @if ($config->countCategories !== 0)
                            <button type="button" class="btn btn-secondary" @click="onSubmit" :disabled="disabled">
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
          window.DesignerScripts = new Vue({
            el: '#addScript',
            data: {
              title: '',
              language: '',
              script_executor_id: null,
              description: '',
              script_category_id: '',
              code: '',
              addError: {},
              selectedUser: '',
              users: [],
              timeout: 60,
              disabled: false,
              createScriptHooks: [],
              script: null,
            },
            methods: {
              onClose() {
                this.title = '';
                this.language = '';
                this.script_executor_id = null;
                this.description = '';
                this.script_category_id = '';
                this.code = '';
                this.timeout = 60;
                this.addError = {};
                this.$refs.categorySelect.resetUncategorized();
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
                  script_executor_id: this.script_executor_id,
                  description: this.description,
                  script_category_id: this.script_category_id,
                  run_as_user_id: this.selectedUser ? this.selectedUser.id : null,
                  code: "[]",
                  timeout: this.timeout
                })
                  .then(response => {
                    ProcessMaker.alert('{{__('The script was created.')}}', 'success');
                    (this.$refs.createScriptHooks || []).forEach(hook => {
                      hook.onsave(response.data);
                    });
                    window.location = "/designer/scripts/" + response.data.id + "/builder";
                  })
                  .catch(error => {
                    this.disabled = false;
                    if (_.get(error, 'response.status') === 422) {
                      this.addError = error.response.data.errors;
                    } else {
                        throw error;
                    }
                  })
              }
            }
          })
        </script>
    @endcan
@append

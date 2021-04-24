@extends('layouts.layout')

@section('title')
    {{__('Configure Script')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Scripts') => route('scripts.index'),
        __('Configure') . " " . $script->title => null,
    ]])
@endsection
@section('content')
    <div class="container" id="editScript">
        <div class="row">
            <div class="col-12">

                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-config"
                           role="tab" aria-controls="nav-config" aria-selected="true">
                           {{__('Configuration')}}
                        </a>
                        @isset($addons)
                            @foreach ($addons as $addon)
                                <a class="nav-item nav-link" id="{{$addon['id'] . '-tab'}}" data-toggle="tab"
                                   href="{{'#' . $addon['id']}}" role="tab" aria-controls="nav-notifications" aria-selected="true">
                                   {{ __($addon['title']) }}
                                </a>
                            @endforeach
                        @endisset
                    </div>
                </nav>

                <div class="card card-body card-body-nav-tabs">
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-config" role="tabpanel" aria-labelledby="nav-config-tab">
                            <div class="form-group">
                            {!! Form::label('title', __('Name')  . '<small class="ml-1">*</small>', [], false) !!}
                            {!! Form::text('title', null, ['id' => 'title','class'=> 'form-control', 'v-model' => 'formData.title',
                            'v-bind:class' => '{"form-control":true, "is-invalid":errors.title}']) !!}
                            <small class="form-text text-muted"
                                  v-if="! errors.title">{{ __('The script name must be distinct.') }}</small>
                            <div class="invalid-feedback" v-if="errors.title">@{{errors.title[0]}}</div>
                        </div>
                        <category-select :label="$t('Category')" api-get="script_categories" api-list="script_categories" v-model="formData.script_category_id" :errors="errors.script_category_id">
                        </category-select>
                        <div class="form-group">
                            <label class="typo__label">{{__('Run script as')}}<small class="ml-1">*</small></label>
                            <select-user v-model="selectedUser" :multiple="false" :class="{'is-invalid': errors.run_as_user_id}">
                            </select-user>
                            <div class="invalid-feedback" v-if="errors.run_as_user_id">@{{errors.run_as_user_id[0]}}</div>
                        </div>

                        <div class="form-group">
                            {!!Form::label('script_executor_id', __('Script Executor'))!!}<small class="ml-1">*</small>
                            {!!Form::select('script_executor_id', [''=>__('Select')] + $scriptExecutors, null, ['class'=>
                            'form-control', 'v-model'=> 'formData.script_executor_id', 'v-bind:class' => '{\'form-control\':true,
                            \'is-invalid\':errors.script_executor_id}']);!!}
                            <div class="invalid-feedback" v-if="errors.script_executor_id">@{{errors.script_executor_id[0]}}</div>
                        </div>

                        <div class="form-group">
                            {!! Form::label('description', __('Description') . '<small class="ml-1">*</small>', [], false) !!}
                            {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control',
                            'v-model' => 'formData.description', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.description}']) !!}
                            <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}</div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('timeout', __('Timeout')) !!}
                            <div class="form-row ml-0">
                                {!! Form::text('timeout', null, ['id' => 'timeout', 'class'=> 'form-control col-1',
                                'v-model' => 'formData.timeout', 'pattern' => '[0-9]*', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.timeout}']) !!}
                                {!! Form::range(null, null, ['id' => 'timeout-range', 'class'=> 'custom-range col ml-1 mt-2',
                                'v-model' => 'formData.timeout', 'min' => 0, 'max' => 300]) !!}
                                <div class="invalid-feedback" v-if="errors.timeout">@{{errors.timeout[0]}}</div>
                            </div>
                            <small class="form-text text-muted"
                                  v-if="! errors.timeout">{{ __('How many seconds the script should be allowed to run (0 is unlimited).') }}</small>
                        </div>
                        <component
                            v-for="(cmp,index) in editScriptHooks"
                            :key="`edit-script-hook-${index}`"
                            :is="cmp"
                            :script="formData"
                            ref="editScriptHooks"
                        ></component>
                        <br>
                        <div class="text-right">
                            {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                            {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
                        </div>
                        </div>
                        @isset($addons)
                            @foreach ($addons as $addon)
                                <div class="tab-pane fade show" id="{{$addon['id']}}" role="tabpanel" aria-labelledby="'nav-tab-'+ {{$addon['id']}}">
                                    {!! $addon['content'] !!}
                                </div>
                            @endforeach
                        @endisset
                    </div>
                </div>
                
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/scripts/editConfig.js')}}"></script>
    <script>
      window.DesignerScripts = new Vue({
        el: '#editScript',
        mixins: addons,
        data() {
          return {
            formData: @json($script),
            selectedUser: @json($selectedUser),
            errors: {
              'title': null,
              'language': null,
              'description': null,
              'timeout': null,
              'status': null
            },
            editScriptHooks: [],
          }
        },
        methods: {
          resetErrors() {
            this.errors = Object.assign({}, {
              title: null,
              language: null,
              description: null,
              status: null
            });
          },
          onClose() {
            window.location.href = '/designer/scripts';
          },
          onUpdate() {
            this.resetErrors();
            ProcessMaker.apiClient.put('scripts/' + this.formData.id, {
              title: this.formData.title,
              language: this.formData.language,
              script_category_id: this.formData.script_category_id,
              description: this.formData.description,
              run_as_user_id: this.selectedUser === null ? null : this.selectedUser.id,
              timeout: this.formData.timeout,
              script_executor_id: this.formData.script_executor_id,
            })
              .then(response => {
                ProcessMaker.alert(this.$t('The script was saved.'), 'success');
                (this.$refs.editScriptHooks || []).forEach(hook => {
                  hook.onsave(this.formData);
                });
                this.onClose();
              })
              .catch(error => {
                if (_.get(error, 'response.status') === 422) {
                  this.errors = error.response.data.errors;
                } else {
                  throw error;
                }
              });
          }
        }
      });
    </script>
@endsection
@extends('layouts.layout', ['title' => __('Processes Management')])

@section('title')
    {{__('Configure Template')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Processes') => route('processes.index'),
        __('Templates') => route('processes.index'),
        $template->name => null,
    ]])
@endsection
@section('content')
    <div class="container" id="configureTemplate" v-cloak>
        <div class="row">
            <div class="col-12">

                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-config"
                           role="tab"
                           aria-controls="nav-config" aria-selected="true">{{__('Configuration')}}</a>                     
                        @isset($addons)
                            @foreach ($addons as $addon)
                                <a class="nav-item nav-link" id="{{$addon['id'] . '-tab'}}" data-toggle="tab"
                                   href="{{'#' . $addon['id']}}" role="tab"
                                   aria-controls="nav-config" aria-selected="true">{{ __($addon['title']) }}</a>
                            @endforeach
                        @endisset
                    </div>
                </nav>
                <div class="card card-body card-body-nav-tabs">
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-config" role="tabpanel"
                             aria-labelledby="nav-config-tab">
                            <required></required>
                            <div class="form-group">
                                {!!Form::label('name', __('Name') . '<small class="ml-1">*</small>', [], false)!!}
                                {!!Form::text('name', null,
                                    [ 'id'=> 'name',
                                        'class'=> 'form-control',
                                        'v-model'=> 'formData.name',
                                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'
                                    ])
                                !!}
                                <small class="form-text text-muted"
                                       v-if="! errors.name">{{ __('The template name must be unique.') }}</small>
                                <div class="invalid-feedback" role="alert" v-if="errors.name">@{{errors.name[0]}}</div>
                            </div>
                            <div class="form-group">
                                {!! Form::label('description', __('Description')  . '<small class="ml-1">*</small>', [], false) !!}
                                {!! Form::textarea('description', null,
                                    ['id' => 'description',
                                        'rows' => 4,
                                        'class'=> 'form-control',
                                        'v-model' => 'formData.description',
                                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}'
                                    ])
                                !!}
                                <div class="invalid-feedback" role="alert" v-if="errors.description">@{{errors.description[0]}}</div>
                            </div>
                            <category-select :label="$t('Category')" api-get="process_categories"
                                api-list="process_categories" v-model="formData.process_category_id"
                                :errors="errors.category"
                                >
                            </category-select>
                            {{-- <div class="form-group">
                                <label class="typo__label">{{__('Process Manager')}}</label>
                                <select-user v-model="manager" :multiple="false" :class="{'is-invalid': errors.manager_id}">
                                </select-user>
                                <div class="invalid-feedback" role="alert" v-if="errors.manager_id">@{{errors.manager_id[0]}}</div>
                            </div> --}}
                            {{-- <div class="form-group">
                                {!! Form::label('status', __('Status')) !!}
                                <select-status v-model="formData.status" :multiple="false"></select-status>
                            </div> --}}
                            <div class="d-flex justify-content-end mt-2">
                                {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                                {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
                            </div>
                        </div>
                        </div>
                        @isset($addons)
                            @foreach ($addons as $addon)
                                <div class="tab-pane fade show" id="{{$addon['id']}}" role="tabpanel"
                                     aria-labelledby="nav-notifications-tab">
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
    <script src="{{mix('js/templates/configure.js')}}"></script>
    <script>
      test = new Vue({
        el: '#configureTemplate',
        mixins: addons,
        data() {
          return {
            formData: @json($template),
            dataGroups: [],
            value: [],
            errors: {
              name: null,
              description: null,
              category: null,
              status: null,
              screen: null
            },
          }
        },       
        methods: {          
          resetErrors() {
            this.errors = Object.assign({}, {
              name: null,
              description: null,
              category: null,
              status: null,
              screen: null
            });
          },
          onClose() {
            window.location.href = '/processes';
          },         
          onUpdate() {
            this.resetErrors();
            let that = this;           
            
            ProcessMaker.apiClient.put('template/process/' + that.formData.id, that.formData)
              .then(response => {                
                ProcessMaker.alert(this.$t('The template was saved.'), 'success', 5, true);
                that.onClose();
              })
              .catch(error => {
                // //define how display errors
                this.errors.name = ['The template name must be unique.'];
                if (error.response.status && error.response.status === 422) {
                  // Validation error
                  that.errors = error.response.data.errors;
                 
                }
              });
          },          
        }
      });
    </script>
@endsection

@section('css')
    <style>
        .card-body-nav-tabs {
            border-top: 0;
        }

        .nav-tabs .nav-link.active {
            background: white;
            border-bottom: 0;
        }

        #table-notifications {
            margin-bottom: 20px;
        }

        #table-notifications th {
            border-top: 0;
        }

        #table-notifications td.notify {
            width: 40%;
        }

        #table-notifications td.action {
            width: 20%;
        }

        .inline-input {
            margin-right: 6px;
        }

        .inline-button {
            background-color: rgb(109, 124, 136);
            font-weight: 100;
        }

        .input-and-select {
            width: 212px;
        }

        .multiselect__tags-wrap {
            display: flex !important;
        }

        .multiselect__tag-icon:after {
            color: white !important;
        }

        .multiselect__option--highlight {
            background: #00bf9c !important;
        }

        .multiselect__option--selected.multiselect__option--highlight {
            background: #00bf9c !important;
        }

        .multiselect__tags {
            border: 1px solid #b6bfc6 !important;
            border-radius: 0.125em !important;
            height: calc(1.875rem + 2px) !important;
        }

        .multiselect__tag {
            background: #788793 !important;
        }

        .multiselect__tag-icon:after {
            color: white !important;
        }
    </style>
@endsection

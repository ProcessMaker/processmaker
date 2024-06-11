@extends('layouts.layout')

@section('title')
    {{__('Configure Screen')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Screens') => route('screens.index'),
        $screen->title => null,
    ]])
@endsection
@section('content')
    <div class="container" id="editGroup">
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
                            <required></required>
                            <div class="form-group">
                                {!! Form::label('title', __('Name')  . '<small class="ml-1">*</small>', [], false) !!}
                                {!! Form::text('title', null, ['id' => 'title','class'=> 'form-control', 'v-model' => 'formData.title',
                                'v-bind:class' => '{"form-control":true, "is-invalid":errors.title}', 'required', 'aria-required' => 'true']) !!}
                                <small class="form-text text-muted" v-if="! errors.title">{{__('The screen name must be unique.') }}</small>
                                <div class="invalid-feedback" role="alert" v-if="errors.title">@{{errors.title[0]}}</div>
                            </div>
                            <div class="form-group">
                                {!! Form::label('description', __('Description') . '<small class="ml-1">*</small>', [], false) !!}
                                {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control',
                                'v-model' => 'formData.description', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.description}', 'required', 'aria-required' => 'true']) !!}
                                <div class="invalid-feedback" role="alert" v-if="errors.description">@{{errors.description[0]}}</div>
                            </div>
                            <category-select :label="$t('Category')" api-get="screen_categories" api-list="screen_categories" v-model="formData.screen_category_id" :errors="errors.screen_category_id">
                            </category-select>
                            <project-select
                                :label="$t('Project')"
                                api-get="projects"
                                api-list="projects"
                                v-model="selectedProjects"
                                :errors="errors.projects">
                            </project-select>
                            <br>
                            <div class="text-right">
                                {!! Form::button(__('Cancel'), [
                                    'class'=>'btn btn-outline-secondary', 
                                    '@click' => 'onClose'
                                ]) !!}
                                {!! Form::button(__('Save and publish'), [
                                    'class'=>'btn btn-secondary ml-2',
                                    '@click' => 'onUpdate'
                                ]) !!}
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
    <script src="{{mix('js/processes/screens/edit.js')}}"></script>
    <script>
        new Vue({
            el: '#editGroup',
            mixins: addons,
            data() {
                return {
                    formData: @json($screen),
                    assignedProjects: @json($assignedProjects),
                    isDraft: @json($isDraft),
                    selectedProjects: '',
                    errors: {
                        'title': null,
                        'type': null,
                        'description': null,
                        'status': null
                    }
                }
            },
            watch: {
                selectedProjects: {
                    handler() {
                        this.formData.projects = this.selectedProjects;
                    }
                }
            },
            methods: {
                resetErrors() {
                    this.errors = Object.assign({}, {
                        title: null,
                        type: null,
                        description: null,
                        status: null
                    });
                },
                onClose() {
                  const queryParams = new URLSearchParams(window.location.search);
                  const projectId = queryParams.get("project_id");
                  window.location.href = projectId ? `/designer/projects/${projectId}`: '/designer/screens';
                },
                onUpdate() {
                    if (this.isDraft) {
                        ProcessMaker.confirmModal(
                            this.$t("Caution!"),
                            this.$t("You are about to publish a draft version. Are you sure you want to proceed?"),
                            "",
                            () => {
                                this.handleUpdate();
                            }
                        );
                    } else {
                        this.handleUpdate();
                    }
                },
                handleUpdate() {
                    this.resetErrors();
                    ProcessMaker.apiClient.put('screens/' + this.formData.id, this.formData)
                        .then(response => {
                            ProcessMaker.alert(this.$t('The screen was saved.'), 'success');
                            this.onClose();
                        })
                        .catch(error => {
                            if (error.response.status && error.response.status === 422) {
                                this.errors = error.response.data.errors;
                            }
                        });
                }
            },
            mounted() {
                this.selectedProjects = this.assignedProjects.length > 0 ?this.assignedProjects.map(project => project.id) : null;
            }
        });
    </script>
@endsection

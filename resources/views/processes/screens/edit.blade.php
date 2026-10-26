@extends('layouts.layoutnextvite')

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
                        <div class="tab-pane show active" id="nav-config" role="tabpanel" aria-labelledby="nav-config-tab">
                            <required></required>
                            <div class="form-group">
                                {{ html()->label(__('Name') . '<small class="ml-1">*</small>', 'title') }}
                                {{ html()->text('title')->id('title')->class('form-control')->attribute('v-model', 'formData.title')->attribute('v-bind:class', '{"form-control":true, "is-invalid":errors.title}')->required()->attribute('aria-required', 'true') }}
                                <small class="form-text text-muted" v-if="! errors.title">{{__('The screen name must be unique.') }}</small>
                                <div class="invalid-feedback" role="alert" v-if="errors.title">@{{errors.title[0]}}</div>
                            </div>
                            <div class="form-group">
                                {{ html()->label(__('Description') . '<small class="ml-1">*</small>', 'description') }}
                                {{ html()->textarea('description')->id('description')->rows(4)->class('form-control')->attribute('v-model', 'formData.description')->attribute('v-bind:class', '{"form-control":true, "is-invalid":errors.description}')->required()->attribute('aria-required', 'true') }}
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
                                {{ html()->button(__('Cancel'), 'button')->class('btn btn-outline-secondary')->attribute('@click', 'onClose') }}
                                {{ html()->button(__('Save and publish'), 'button')->class('btn btn-secondary ml-2')->attribute('@click', 'onUpdate') }}
                            </div>
                        </div>
                        @isset($addons)
                            @foreach ($addons as $addon)
                                <div class="tab-pane show" id="{{$addon['id']}}" role="tabpanel" aria-labelledby="'nav-tab-'+ {{$addon['id']}}">
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
<script>
    window.temporal = window.temporal || {};
    window.temporal.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
    window.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
    window.temporal.screen = @json($screen);
    window.temporal.assignedProjects = @json($assignedProjects);
    window.temporal.isDraft = @json($isDraft);
</script>
    @vite(['resources/js/processes/screens/loaderScreens.js'])
    @vite(['resources/js/processes/screens/edit.js'])
@endsection

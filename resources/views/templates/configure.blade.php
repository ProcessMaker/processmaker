@extends('layouts.layout', ['title' => __('Processes Management')])

@section('title')
    {{__('Configure Template')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route( $route['action'] . '.index'),
        $route['label'] => route($route['action'] . '.index'),
        __('Templates') => route($route['action'] . '.index') . $templateBreadcrumb,
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
                            @if ($type === 'process')
                                <process-template-configurations
                                    :template-data="formData"
                                    :permission="{{ \Auth::user()->hasPermissionsFor('process-templates') }}"
                                    :response-errors="errors"
                                    @updated="handleUpdatedTemplate"
                                />
                            @endif
                            @if ($type === 'screen')
                                <screen-template-configurations
                                    :template-data="formData"
                                    :permission="{{ \Auth::user()->hasPermissionsFor('screen-templates') }}"
                                    :screen-types="screenTypes"
                                    :response-errors="errors"
                                    @updated="handleUpdatedTemplate"
                                />
                            @endif
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
                    <div class="d-flex justify-content-end mt-2">
                        {!! Form::button(__('Cancel'),
                            [
                                'class'=>'btn btn-outline-secondary',
                                '@click' => 'onClose'
                            ])
                        !!}
                        {!! Form::button(__('Save'),
                            [
                                ':disabled' => 'isDefaultProcessmakerTemplate',
                                'class'=>'btn btn-secondary ml-2',
                                '@click' => 'onUpdate'
                            ])
                        !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script>
        window.ProcessMaker.templateConfigurations = {
            data: @json($template),
            templateType: @json($type),
            screenTypes: @json($screenTypes),
        }
    </script>
    <script src="{{mix('js/templates/configure.js')}}"></script>
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

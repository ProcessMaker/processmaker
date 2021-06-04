@extends('layouts.layout')

@section('title')
    {{__('API Connector')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('API Connector') => null,
    ]])
@endsection
@section('content')
    <div class="px-3 page-content" id="process-connectors-listing">
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
                @can('create-api_connectors')
                    <div class="d-flex ml-md-2 flex-column flex-md-row">
                        <button type="button" id="create_envvar" class="btn btn-secondary" data-toggle="modal"
                                data-target="#createApiConnector">
                            <i class="fas fa-plus"></i> {{__('API Connector')}}
                        </button>
                    </div>
                @endcan
            </div>
        </div>
        <connectors-listing ref="listVariable" :filter="filter"
                           :permission="{{ \Auth::user()->hasPermissionsFor('api_connectors') }}"
                           @delete="deleteVariable"></connectors-listing>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/api-connectors/index.js')}}"></script>
@endsection

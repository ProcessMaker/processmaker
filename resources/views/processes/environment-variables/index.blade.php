@extends('layouts.layout')

@section('title')
    {{__('Environment Variables')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Environment Variables') => null,
    ]])
@endsection
@section('content')
    <div class="px-3 page-content" id="process-variables-listing">
        <div id="search-bar" class="search mb-3" vcloak>
            <div class="d-flex flex-column flex-md-row">
                <div class="flex-grow-1">
                    <div id="search" class="mb-3 mb-md-0">
                        <div class="input-group w-100">
                            <input id="search-box" v-model="filter" class="form-control" placeholder="{{__('Search')}}"  aria-label="{{__('Search')}}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                @can('create-environment_variables')
                    <create-environment-variable-modal></create-environment-variable-modal>
                @endcan
            </div>
        </div>
        <variables-listing ref="listVariable" :filter="filter"
                           :permission="{{ \Auth::user()->hasPermissionsFor('environment_variables') }}"
                           @delete="deleteVariable"></variables-listing>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/environment-variables/index.js')}}"></script>
@endsection

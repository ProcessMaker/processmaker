@extends('layouts.layout')

@section('title')
{{__('Environment Variables')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
<div class="container page-content" id="variablesIndex">
    <h1>{{__('Environment Variables')}}</h1>
    <div class="row">
        <div class="col">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
                <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
            </div>

        </div>
        <div class="col-8" align="right">
            <a href="#" @click="show" class="btn btn-action"><i class="fas fa-plus"></i> {{__('Environment Variable')}}</a>
        </div>
    </div>
    <div class="container-fluid">
        <variables-listing :filter="filter"></variables-listing>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/processes/environment-variables/index.js')}}"></script>
@endsection

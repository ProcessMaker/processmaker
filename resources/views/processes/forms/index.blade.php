@extends('layouts.layout')

@section('title')
{{__('Forms')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
<div class="container page-content" id="formIndex">
    <h1>{{__('Forms')}}</h1>
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
            <a href="#" @click="show" class="btn btn-action"><i class="fas fa-plus"></i> {{__('Form')}}</a>
        </div>
    </div>
    <div class="container-fluid">
        <modal-create-form :show="formModal" @close="formModal=false" v-on:reload="reload">
        </modal-create-form>
        <form-listing ref="formListing" :filter="filter" v-on:reload="reload"></form-listing>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/processes/forms/index.js')}}"></script>
@endsection
@extends('layouts.layout')

@section('title')
    {{__('Forms')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="container page-content" id="formIndex">
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Forms')}}</h1>
                        <input id="processes-listing-search" v-model="filter" class="form-control col-sm-3"
                               placeholder="{{__('Search')}}...">
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                        <a href="#" @click="show" class="btn btn-action">
                            <i class="fas fa-plus"></i> {{__('Form')}}
                        </a>
                    </div>
                </div>
                <modal-create-form :show="formModal" @close="formModal=false" v-on:reload="reload">
                </modal-create-form>
                <form-listing ref="formListing" :filter="filter" v-on:reload="reload"></form-listing>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/forms/index.js')}}"></script>
@endsection

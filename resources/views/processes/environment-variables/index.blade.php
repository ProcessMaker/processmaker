@extends('layouts.layout')

@section('title')
  {{__('Environment Variables')}}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container page-content" id="variablesIndex">
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Environment Variables')}}</h1>
                        <input id="script-listing-search" v-model="filter" class="form-control col-sm-3"
                               placeholder="{{__('Search')}}...">
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                        <a href="#" class="btn btn-action">
                            <i class="fas fa-plus"></i> {{__('Environment Variable')}}
                        </a>
                    </div>
                </div>
                <variables-listing :filter="filter"></variables-listing>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/environment-variables/index.js')}}"></script>
@endsection

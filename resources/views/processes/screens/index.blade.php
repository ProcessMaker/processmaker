@extends('layouts.layout')

@section('title')
    {{__('Screens')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="container page-content" id="screenIndex">
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Screens')}}</h1>
                        <input id="processes-listing-search" v-model="filter" class="form-control col-sm-3"
                               placeholder="{{__('Search')}}...">
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                        <a href="#" @click="show" class="btn btn-action">
                            <i class="fas fa-plus"></i> {{__('Screen')}}
                        </a>
                    </div>
                </div>
                <modal-create-screen :show="screenModal" @close="screenModal=false" v-on:reload="reload">
                </modal-create-screen>
                <screen-listing ref="screenListing" :filter="filter" v-on:reload="reload"></screen-listing>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/screens/index.js')}}"></script>
@endsection

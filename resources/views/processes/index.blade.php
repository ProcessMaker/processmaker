@extends('layouts.layout')

@section('title')
  {{__('Processes')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="container page-content" id="processIndex">
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Processes')}}</h1>
                        <input id="processes-listing-search" v-model="filter" class="form-control col-sm-3"
                               placeholder="{{__('Search')}}...">
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                        <a href="#" @click="show" class="btn btn-action">
                            <i class="fas fa-plus"></i> {{__('Process')}}
                        </a>
                    </div>
                </div>
                <modal-create-process :show="processModal" @close="processModal=false" :process-uuid="processUuid"
                                      v-on:reload="reload"></modal-create-process>
                <processes-listing ref="processListing" :filter="filter" v-on:edit="edit"
                                   v-on:reload="reload"></processes-listing>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/index.js')}}"></script>
@endsection

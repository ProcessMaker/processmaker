@extends('layouts.layout')

@section('title')
    {{__('Requests')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_request')])
@endsection

@section('content')
    <div class="container page-content" id="requests-listing">
        <div class="row">
            <div class="col-sm-12">
                <b-card-group deck class="mb-3">
                    <b-card header="<i class='fas fa-clipboard fa-2x'></i>"
                            header-class="d-flex align-items-center justify-content-center card-size-header border-0"
                            text-variant="white" class="bg-warning mb-3 d-flex flex-row  card-border border-0">
                        <a href="#" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">8</h1>
                            <h4 class="card-text">{{__('All Request')}}</h4>
                        </a>
                    </b-card>
                    <b-card header="<i class='fas fa-th-list fa-2x'></i>"
                            header-class="d-flex align-items-center justify-content-center card-size-header border-0"
                            text-variant="white" class="bg-info mb-3 d-flex flex-row card-border border-0">
                        <a href="#" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">1</h1>
                            <h4 class="card-text">{{__('Started by Me')}}</h4>
                        </a>
                    </b-card>
                    <b-card header="<i class='fas fa-clipboard-list fa-2x'></i>"
                            header-class="d-flex align-items-center justify-content-center card-size-header border-0"
                            text-variant="white" class="bg-success mb-3 d-flex flex-row card-border border-0">
                        <a href="#" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">7</h1>
                            <h4 class="card-text">{{__('In Progress')}}</h4>
                        </a>
                    </b-card>
                    <b-card header="<i class='fas fa-clipboard-check fa-2x'></i>"
                            header-class="d-flex align-items-center justify-content-center card-size-header border-0"
                            text-variant="white" class="bg-primary mb-3 d-flex flex-row card-border border-0">
                        <a href="#" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">2</h1>
                            <h4 class="card-text">{{__('Complete')}}</h4>
                        </a>
                    </b-card>
                </b-card-group>

                <div class="row">
                    <div class="col-md-4 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Requests')}}</h1>
                    </div>
                    <span class="col-md-8 col-sm-12 actions">
                        <div class="col-md-5 pull-right form-group has-search">
                            <span class="fa fa-search form-control-feedback"></span>
                            <input id="request-listing-search" v-model="filter" type="text" class="form-control"
                                   placeholder="{{__('Search')}}...">
                        </div>
                    </span>
                </div>
                <requests-listing :filter="filter"></requests-listing>
            </div>
        </div>
    </div>

    {{-- <div class="container" id="requests-listing">
         <br>
             <b-card-group deck class="mb-3">
                 <b-card no-body text-variant="white" class="text-center" style="left: -15px; background: rgb(232, 181, 22);;">
                     <a href="#" class="card-link text-light ">
                     <div class="row d-flex">
                         <div class="col-5 align-self-center">
                             <i class="fa fa-clipboard fa-3x"></i>
                         </div>
                         <div class="col-7 text-left" style="left: -15px; background: #efc441;">
                             <div class="font-weight-bold"><h2 style="font-size: 2.5rem;">10</h2></div>
                             <div>{{__('All Request')}}</div>
                         </div>
                     </div>
                     </a>
                 </b-card>
                 <b-card no-body text-variant="white" class="text-center" style="background: rgb(16, 136, 154);">
                     <a href="#" class="card-link text-light">
                         <div class="row d-flex">
                             <div class="col-5 align-self-center">
                                 <i class="fa fa-th-list fa-3x"></i>
                             </div>
                             <div class="col-7 text-left" style="left: -15px; background: #17a2b8;">
                                 <div class="font-weight-bold"><h2 style="font-size: 2.5rem;">1</h2></div>
                                 <div>{{__('Started by Me')}}</div>
                             </div>
                         </div>
                     </a>
                 </b-card>
                 <b-card no-body text-variant="white" class="text-center" style="background: #03967b;">
                     <a href="#" class="card-link text-light">
                         <div class="row d-flex">
                             <div class="col-5 align-self-center">
                                 <i class="fa fa-clipboard-list fa-3x"></i>
                             </div>
                             <div class="col-7 text-left" style="left: -15px; background: #00bf9c;">
                                 <div class="font-weight-bold"><h2 style="font-size: 2.5rem;">7</h2></div>
                                 <div>{{__('In Progress')}}</div>
                             </div>
                         </div>
                     </a>
                 </b-card>
                 <b-card no-body text-variant="white" class="text-center" style="background: #047bb3">
                     <a href="#" class="card-link text-light">
                         <div class="row d-flex">
                             <div class="col-5 align-self-center">
                                 <i class="fa fa-clipboard-check fa-3x"></i>
                             </div>
                             <div class="col-7 text-left" style="left: -15px; background: #0096dd;">
                                 <div class="font-weight-bold"><h2 style="font-size: 2.5rem;">2</h2></div>
                                 <div>{{__('Complete')}}</div>
                             </div>
                         </div>
                     </a>
                 </b-card>
             </b-card-group>
         <br>

         <div class="row">
             <div class="col-3">
                 <h1>{{__('Requests')}}</h1>
             </div>
             --}}{{--<div class="col-5" style="margin-top:20px">
                 <a href="#" class="btn btn-danger" @click="loadRequestsOverdue"> OVERDUE </a>
                 &nbsp;
                 <a href="#" class="btn btn-warning" @click="loadRequestsAtRisk"> AT RISK </a>
                 &nbsp;
                 <a href="#" class="btn btn-info" @click="loadRequestsOnTime"> ON TIME </a>
             </div>--}}{{--
             <div class="col-4" style="margin-top:20px">
                 <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
             </div>
         </div>
         <div style="margin-top:-20px;">
             <requests-listing :filter="filter"></requests-listing>
         </div>
     </div>--}}
@endsection

@section('js')
    <script src="{{mix('js/requests/index.js')}}"></script>
@endsection

@section('css')
    <style>
        .has-search .form-control {
            padding-left: 2.375rem;
        }

        .has-search .form-control-feedback {
            position: absolute;
            z-index: 2;
            display: block;
            width: 2.375rem;
            height: 2.375rem;
            line-height: 2.375rem;
            text-align: center;
            pointer-events: none;
            color: #aaa;
        }

        .card-border {
            border-radius: 4px !important;
        }

        .card-size-header {
            width: 90px;
        }
    </style>
@endsection


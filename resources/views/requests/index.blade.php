@extends('layouts.layout', ['title' => 'Requests'])

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_request')])
@endsection

@section('content')
    <div class="container page-content" id="requests-listing">
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-md-8 d-flex align-items-center col-sm-12">
                    <h1 class="page-title">Requests</h1>
                    <a href="#" class="btn btn-action" @click="loadRequestsOverdue"> OVERDUE </a>
                    &nbsp;
                    <a href="#" class="btn btn-action" @click="loadRequestsAtRisk"> AT RISK </a>
                    &nbsp;
                    <a href="#" class="btn btn-action" @click="loadRequestsOnTime"> ON TIME </a>
                </div>
                <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                    <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                </div>
            </div>
            <requests-listing :filter="filter"></requests-listing>
        </div>
    </div>
    </div>
@endsection

@section('js')
<script src="{{mix('js/requests/index.js')}}"></script>
@endsection

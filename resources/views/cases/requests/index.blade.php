@extends('layouts.layout', ['title' => 'User Management'])

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container page-content" id="requests-listing">
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-md-8 d-flex align-items-center col-sm-12">
                    <h1 class="page-title">Requests</h1>
                    <a href="#" class="btn btn-action"> OVERDUE </a>
                    <a href="#" class="btn btn-action"> AT RISK </a>
                    <a href="#" class="btn btn-action"> ON TIME </a>
                    <input v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
                </div>
            </div>
            <requests-listing :filter="filter"></requests-listing>
        </div>
    </div>
    </div>
@endsection

@section('js')
<script src="{{mix('js/cases/requests/index.js')}}"></script>
@endsection

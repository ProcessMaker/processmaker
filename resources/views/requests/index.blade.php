@extends('layouts.layout', ['title' => 'Requests'])

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_request')])
@endsection

@section('content')

    <div class="container" id="requests-listing">

    <div class="row">
          <div class="col-3">
                      <h1>{{__($title)}}</h1>
                      </div>
                          <div class="col-5" style="margin-top:20px">
                            <a href="#" class="btn btn-danger" @click="loadRequestsOverdue"> OVERDUE </a>
                            &nbsp;
                            <a href="#" class="btn btn-warning" @click="loadRequestsAtRisk"> AT RISK </a>
                            &nbsp;
                            <a href="#" class="btn btn-info" @click="loadRequestsOnTime"> ON TIME </a>
                          </div>
                <div class="col-4" style="margin-top:20px">
                    <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                </div>
            </div>
            <div style="margin-top:-20px;">
            <requests-listing :filter="filter" status="{{ $status }}"></requests-listing>
          </div>
        </div>
@endsection

@section('js')
<script src="{{mix('js/requests/index.js')}}"></script>
@endsection

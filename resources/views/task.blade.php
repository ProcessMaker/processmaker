@extends('layouts.layout')

@section('content')

    <div class="container page-content" id="tasks">
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Tasks')}}</h1>
                        <input id="tasks-listing-search" v-model="filter" class="form-control col-sm-3"
                               placeholder="{{__('Search')}}...">
                    </div>
                </div>
                <tasks-list :filter="filter"></tasks-list>
            </div>
        </div>
    </div>

@endsection

@section('sidebar')
  @include('sidebars.default', ['sidebar'=> $sidebar_task])
@endsection

@section('js')
    <script src="{{mix('js/tasks/index.js')}}"></script>
@endsection

@extends('layouts.layout', ['title' => 'Tasks Management'])

@section('sidebar')
  @include('sidebars.default', ['sidebar'=> $sidebar_admin])
@endsection

@section('content')
    <div class="container page-content" id="tasks-listing">
        <!-- Task Add Dialog -->
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-md-8 d-flex align-items-center col-sm-12">
                <h1 class="page-title">Tasks</h1>
                <input id="tasks-listing-search" v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
                </div>
            </div>
            <tasks-listing :filter="filter"></tasks-listing>
        </div>
    </div>
    </div>
@endsection

@section('js')
<script src="{{mix('js/management/tasks/index.js')}}"></script>
@endsection

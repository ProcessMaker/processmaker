@extends('layouts.layout', ['title' => __('Tasks Management')])

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container page-content" id="tasks-listing">
        <!-- Task Add Dialog -->
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Tasks')}}</h1>
                        <input id="tasks-listing-search" v-model="filter" class="form-control col-sm-3"
                               placeholder="{{__('Search')}}...">
                    </div>
                </div>
                <tasks-listing uid="{{$process->uid}}" :filter="filter"></tasks-listing>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/tasks/index.js')}}"></script>
@endsection

@extends('layouts.layout', ['title' => __('UI Customization')])

@section('sidebar')
    @include('sidebars.default', ['sidebar'=> $sidebar_admin])
@endsection

@section('content')
<div>
    <h1 class="page-title">{{__('UI Customization')}}</h1>
    <div>
        <p>Add your company logo</p>
        <div>
        <input id="tasks-listing-search" v-model="filter" class="form-control col-sm-3"
                               placeholder="{{__('Search')}}...">
            <p>Search Bar</p>
        </div>
    </div>
    <div>
        <p>Create a color scheme to customize your UI</p>
        <div>
            <p>search bar</p>
            <p>Search Bar</p>
        </div>
    </div>
</div>



@endsection

@section('js')
    <script src="{{mix('js/processes/tasks/index.js')}}"></script>
@endsection
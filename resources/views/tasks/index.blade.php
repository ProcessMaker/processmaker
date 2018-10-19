@extends('layouts.layout')

@section('title')
  {{__('Tasks')}}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_task')])
@endsection

@section('content')
<div class="container" id="tasks">
    <div class="row">
        <div class="col">
            <h1>{{__('Tasks')}}</h1>
        </div>
        <div class="col-4" style="margin-top:20px">
            <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
        </div>
    </div>
    <div style="margin-top:-20px;">
        <tasks-list :filter="filter"></tasks-list>
    </div>
</div>
@endsection

@section('js')
    <script src="{{mix('js/tasks/index.js')}}"></script>
@endsection

@extends('layouts.layout')

@section('title')
{{__('Tasks')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_task')])
@endsection

@section('content')
<div class="container mt-2" id="tasks">
    <div class="row">
        <div class="col">
            <h1 class="page-title">{{__('Tasks')}}</h1>
        </div>
        <div class="col mt-2">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
                <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
            </div>
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
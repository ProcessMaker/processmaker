@extends('layouts.layout')

@section('title')
{{__($title)}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_task')])
@endsection

@section('content')
<div class="container page-content" id="tasks">
  <h1>{{__($title)}}</h1>
  <div class="row">
    <div class="col">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text" id="basic-addon1">
            <i class="fas fa-search"></i>
          </span>
        </div>
        <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
      </div>

    </div>
    <div class="col-8" align="right">
      
    </div>
  </div>
  <div class="container-fluid">
    <tasks-list :filter="filter"></tasks-list>
  </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/tasks/index.js')}}"></script>
@endsection
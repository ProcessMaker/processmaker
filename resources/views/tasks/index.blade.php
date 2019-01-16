@extends('layouts.layout')

@section('title')
{{__($title)}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_task')])
@endsection

@section('content')
@include('shared.breadcrumbs', ['routes' => [
    __('Tasks') => route('tasks.index'),
    __($title) => null,
]])
<div class="container page-content" id="tasks">

  <div class="row">
    <div class="col" align="right">
      <b-alert class="align-middle" show variant="danger" v-cloak v-if="inOverdueMessage.length>0"
               style="text-align: center; margin-top:20px;" >
        @{{ inOverdueMessage }}
      </b-alert>
    </div>
  </div>

  <div class="row">
    <div class="col">
      <h1>{{__($title)}}</h1>
    </div>
  </div>

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
    <div class="col-6" align="right">
      {{--<b-alert show variant="warning" v-cloak v-if="inOverdueMessage.length>0" style="text-align: left;">--}}
        {{--@{{ inOverdueMessage }}--}}
      {{--</b-alert>--}}
    </div>
  </div>
  <div class="container-fluid">
    <tasks-list :filter="filter" @in-overdue="setInOverdueMessage"></tasks-list>
  </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/tasks/index.js')}}"></script>
@endsection
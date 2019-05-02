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
  <div id="search" class="row mt-2 bg-light p-2" v-if="advanced == false">
    <div class="col">
        <multiselect 
        v-model="task" 
        @search-change="getTasks" 
        @input="buildPmql"
        :select-label="''" 
        :loading="isLoading.task" 
        open-direction="bottom" 
        label="name" 
        :options="taskOptions"
        :track-by="'id'"
        :multiple="true" 
        :limit="1" 
        :limit-text="count => `+${count}`" 
        placeholder="Task"></multiselect>
    </div>
    <div class="col">
        <multiselect
        v-model="request"
        :select-label="''" 
        @input="buildPmql"
        :loading="isLoading.request"
        open-direction="bottom"
        label="name"
        :options="requestOptions"
        track-by="value"
        :multiple="true"
        :limit="1"
        :limit-text="count => `+${count}`"
        placeholder="Request"></multiselect>
    </div>
    <div class="col">
        <multiselect 
        v-model="assignee" 
        @search-change="getAssignee" 
        @input="buildPmql"
        :select-label="''" 
        :loading="isLoading.assignee" 
        open-direction="bottom" 
        label="name" 
        :options="assigneeOptions" 
        :track-by="'id'"
        :multiple="true" 
        :limit="1" 
        :limit-text="count => `+${count}`" 
        placeholder="Assginee">

        </multiselect>
    </div>
    <div class="col mt-2" align="right">
        <button class="btn btn-default" @click="runSearch"><i class="fas fa-search text-secondary"></i></button>
        <a class="text-primary ml-3" @click="advanced = true">{{__('Advanced')}}</a>
    </div>
</div>  
<div v-if="advanced == true" class="search row mt-2 bg-light p-2">
    <div class="col-10 form-group">
        <input type="text" class="form-control" placeholder="PMQL" v-model="pmql">
    </div>
    <div class="col mt-2" align="right">
        <button class="btn btn-default" @click="runSearch(true)"><i class="fas fa-search text-secondary"></i></button>
        <a class="text-primary ml-3" @click="advanced = false">{{__('Basic')}}</a>
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
<style>
  #search {
    border: 1px solid rgba(0, 0, 0, 0.125);
    margin-left: -1px;
    margin-right: -1px;
  }
</style>
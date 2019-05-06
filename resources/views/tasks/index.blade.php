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
  <div id="search-bar" class="search mt-2 bg-light p-2" vcloak>
      <div class="d-flex">
          <div class="flex-grow-1">
              <div id="search-dropdowns" v-if="! advanced" class="row">
                  <div class="col-4">
                      <multiselect 
                      v-model="request" 
                      @search-change="getRequests" 
                      @input="buildPmql"
                      :select-label="''" 
                      :loading="isLoading.request" 
                      open-direction="bottom" 
                      label="name" 
                      :options="requestOptions"
                      :track-by="'id'"
                      :multiple="true" 
                      placeholder="Request">
                          <template slot="selection" slot-scope="{ values, search, isOpen }">
                              <span class="multiselect__single" v-if="values.length > 1 && !isOpen">@{{ values.length }} {{ __('requests') }}</span>
                          </template>
                      </multiselect>
                  </div>
                  <div class="col-4">
                      <multiselect 
                      v-model="name" 
                      @search-change="getNames" 
                      @input="buildPmql"
                      :select-label="''" 
                      :loading="isLoading.name" 
                      open-direction="bottom" 
                      label="name" 
                      :options="nameOptions"
                      :track-by="'name'"
                      :multiple="true" 
                      placeholder="Task">
                          <template slot="selection" slot-scope="{ values, search, isOpen }">
                              <span class="multiselect__single" v-if="values.length > 1 && !isOpen">@{{ values.length }} {{ __('names') }}</span>
                          </template>
                      </multiselect>
                  </div>
                  <div class="col-4">
                      <multiselect
                      v-model="status"
                      :select-label="''" 
                      @input="buildPmql"
                      :loading="isLoading.status"
                      open-direction="bottom"
                      label="name"
                      :options="statusOptions"
                      track-by="value"
                      :multiple="true"
                      placeholder="Status">
                          <template slot="selection" slot-scope="{ values, search, isOpen }">
                              <span class="multiselect__single" v-if="values.length > 1 && !isOpen">@{{ values.length }} {{ __('statuses') }}</span>
                          </template>
                      </multiselect>
                  </div>
              </div>
              <div id="search-manual" v-if="advanced">
                  <input ref="search_input" type="text" class="form-control" placeholder="PMQL" v-model="pmql">
              </div>
          </div>
          <div class="flex-shrink-0">
              <div id="search-actions">
                  <div v-if="! advanced">
                      <b-btn variant="primary" @click="runSearch()" v-b-tooltip.hover :title="$t('Search')"><i class="fas fa-search"></i></b-btn>
                      <b-btn variant="secondary" @click="toggleAdvanced" v-b-tooltip.hover :title="$t('Advanced Search')"><i class="fas fa-ellipsis-h"></i></b-btn>
                  </div>
                  <div v-if="advanced">
                      <b-btn variant="primary" @click="runSearch(true)" v-b-tooltip.hover :title="$t('Search')"><i class="fas fa-search"></i></b-btn>
                      <b-btn variant="success" @click="toggleAdvanced" v-b-tooltip.hover :title="$t('Basic Search')"><i class="fas fa-ellipsis-h"></i></b-btn>
                  </div>
              </div>
          </div>
      </div>
  </div>  
  <div class="container-fluid">
    <tasks-list ref="taskList" :filter="filter" @in-overdue="setInOverdueMessage"></tasks-list>
  </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/tasks/index.js')}}"></script>
@endsection

@section('css')
<style>
  #search-bar {
      height: 59px;
      max-height: 59px;
  }
  
  #search-manual input {
      border: 1px solid #e8e8e8;
      border-radius: 5px;
      color: gray;
      height: 41px;
  }

  #search-dropdowns {
      padding: 0 11px;
  }

  #search-dropdowns .col-4 {
      padding: 0 4px;
  }
  
  #search-actions button {
      display: inline-block;
      float: left;
      height: 41px;
      margin-left: 8px;
  }
  
  .multiselect__placeholder {
      padding-top: 1px;
  }
  
  .multiselect__single {
      padding-bottom: 2px;
      padding-top: 2px;
  }

  .search {
      border: 1px solid rgba(0, 0, 0, 0.125);
      margin-left: -1px;
      margin-right: -1px;
  }
  .has-search .form-control {
      padding-left: 2.375rem;
  }

  .has-search .form-control-feedback {
      position: absolute;
      z-index: 2;
      display: block;
      width: 2.375rem;
      height: 2.375rem;
      line-height: 2.375rem;
      text-align: center;
      pointer-events: none;
      color: #aaa;
  }

  .card-border {
      border-radius: 4px !important;
  }

  .card-size-header {
      width: 90px;
  }
  .option__image {
      width:27px;
      height: 27px;
      border-radius: 50%;
  }
  .initials {
      display:inline-block;
      text-align: center;
      font-size: 12px;
      max-width:25px;
      max-height: 25px;
      min-width:25px;
      min-height: 25px;
      border-radius: 50%;
  }
</style>
@endsection

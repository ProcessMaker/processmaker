@extends('layouts.layout')

@section('title')
{{__('Notifications Inbox')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_notifications')])
@endsection

@section('breadcrumbs')
@include('shared.breadcrumbs', ['routes' => [
  $title => null,
]])
@endsection
@section('content')
<div class="px-3 page-content" id="notifications">
  <div id="search-bar" class="search mt-2 bg-light" vcloak>
    <div class="d-flex flex-column flex-md-row">
      <div class="flex-grow-1">
        <div id="search" class="mb-3 mb-md-0">
          <div class="input-group w-100">
            <input v-model="filter" class="form-control" placeholder="{{__('Search')}}">
            <div class="input-group-append">
              <button type="button" class="btn btn-primary" data-original-title="Search"><i class="fas fa-search"></i></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="container-fluid">
    <notifications-list :filter="filter" status="{{ $status }}"></notifications-list>
  </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/notifications/index.js')}}"></script>
@endsection

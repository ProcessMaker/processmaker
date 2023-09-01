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
  <div class="card card-body table-card">
    <!-- Add nav tabs: All, Inbox, Comments -->
    <ul class="nav nav-tabs" id="notificationTabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="all-tab" data-toggle="tab" href="#all" role="tab" aria-controls="all" aria-selected="true">All</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="inbox-tab" data-toggle="tab" href="#inbox" role="tab" aria-controls="inbox" aria-selected="false">Inbox</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="comments-tab" data-toggle="tab" href="#comments" role="tab" aria-controls="comments" aria-selected="false">Comments</a>
      </li>
    </ul>
    <!-- Tab content -->
    <div class="tab-content" id="notificationTabContent">
      <div id="search-bar" class="search mb-3" vcloak style="margin: 16px;">
        <div class="d-flex flex-column flex-md-row">
          <div class="flex-grow-1">
            <div id="search" class="mb-3 mb-md-0">
              <div class="input-group w-100">
                <input id="search-box" v-model="filter" class="form-control" placeholder="{{__('Search')}}"  aria-label="{{__('Search')}}">
                <div class="input-group-append">
                  <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}"><i class="fas fa-search"></i></button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
        <!-- All content -->
        <div class="container-fluid" style="margin: 16px;">
          <notifications-list :filter="filter" :status="currentTab"></notifications-list>
        </div>
      </div>
      <div class="tab-pane fade" id="inbox" role="tabpanel" aria-labelledby="inbox-tab">
        <!-- Inbox content -->
        <div class="container-fluid" style="margin: 16px;">
          <notifications-list :filter="filter" :status="currentTab"></notifications-list>
        </div>
      </div>
      <div class="tab-pane fade" id="comments" role="tabpanel" aria-labelledby="comments-tab">
        <!-- Comments content-->
        <div class="container-fluid" style="margin: 16px;">
          <notifications-list :filter="filter" :status="currentTab"></notifications-list>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/notifications/index.js')}}"></script>
@endsection

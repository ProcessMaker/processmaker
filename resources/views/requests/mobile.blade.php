@extends('layouts.mobilenextvite')
@section('title')
{{__($title)}}
@endsection
@section('content_mobile')
<div class="d-flex flex-column">
  <div class="flex-fill">
    <div class="row">
      <div class="col-12">
        <div id="requests-mobile" class="card card-body p-3">
        <filter-mobile type="requests" @filterspmqlchange="onFiltersPmqlChange"></filter-mobile>
          <mobile-requests ref="requestsMobileList" :filter="filter" :pmql="fullPmql"></mobile-requests>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script>
  window.temporal = {};
  window.temporal.user = @json($currentUser);
  window.temporal.status = '{{ $type }}';
</script>
@vite(['resources/js/requests/loaderRequests.js'])
@vite(['resources/js/requests/mobile.js'])
@endsection

@section('css')
<style>
  #requests-mobile {
    background-color: #F7F9FB;
  }
</style>
@endsection

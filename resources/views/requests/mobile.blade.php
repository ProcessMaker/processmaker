@extends('layouts.mobile')
@section('title')
{{__($title)}}
@endsection
@section('content_mobile')
<div class="d-flex flex-column" style="min-height: 100vh" id="requests-listing">
<div class="flex-fill">
  <div class="row">
    <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
      <div class="card card-body p-3">
          <span>Welcome Mobile ProcessMaker</span>
      </div>

    </div>


  </div>

  <div class="row">
    <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
     <requests-nav-bar :type="'tab_tasks'"></requests-nav-bar>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
    
    </div>
  </div>

</div>
@endsection

@section('js')
<script src="{{ asset('../../js/requests/index.js') }}"></script>
@endsection

@section('css')

@endsection

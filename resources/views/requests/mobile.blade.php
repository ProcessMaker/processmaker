@extends('layouts.mobile')
@section('title')
{{__($title)}}
@endsection
@php
  $path = Request::path();
@endphp
@section('content_mobile')
<div id="requests-listing" class="d-flex flex-column" style="min-height: 100vh">
<div class="flex-fill">
  <div class="row">
    <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
      <div class="card card-body p-3">
          <span>Welcome Mobile ProcessMaker</span>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')

@endsection

@section('css')

@endsection

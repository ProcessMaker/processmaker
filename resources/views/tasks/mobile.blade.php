@extends('layouts.mobile')
@section('title')
{{__($title)}}
@endsection
@php
  $path = Request::path();
@endphp
@section('content_mobile')
<div class="d-flex flex-column">
  <div class="flex-fill">
    <div class="row">
      <div class="col-12">
        <div id="tasks-mobile" class="card card-body p-3">
          <mobile-tasks />
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/tasks/mobile.js')}}"></script>
@endsection

@section('css')

@endsection

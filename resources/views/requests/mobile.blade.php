@extends('layouts.mobile')
@section('title')
{{__($title)}}
@endsection
@section('content_mobile')
<div class="d-flex flex-column">
  <div class="flex-fill">
    <div class="row">
      <div class="col-12">
        <div id="requests-mobile" class="card card-body p-3">
          <mobile-requests />
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/requests/mobile.js')}}"></script>
@endsection

@section('css')

@endsection

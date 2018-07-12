@extends('layouts.layout')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-sm">
            <div id="status">
                <request-status process-uid="{{$instance->process->uid}}" instance-uid="{{$instance->uid}}"></request-status>
            </div>
        </div>
    </div>
</div>
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection

@section('js')
<script src="{{mix('js/request/status.js')}}"></script>
@endsection

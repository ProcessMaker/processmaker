@extends('layouts.layout',['title'=>'Task Detail'])

@section('content')
<div id="status">
  {{-- <request-status process-uid="{{$instance->process->uid}}" instance-uid="{{$instance->uid}}"></request-status> --}}
</div>
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection

@section('js')
<script src="{{mix('js/request/status.js')}}"></script>
@endsection

@extends('layouts.layout')

@section('title')
  {{__('Task Detail')}}
@endsection

@section('content')
<div id="status">
  {{-- <request-status process-id="{{$instance->process->id}}" instance-id="{{$instance->id}}"></request-status> --}}
</div>
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection

@section('js')
<script src="{{mix('js/request/status.js')}}"></script>
@endsection

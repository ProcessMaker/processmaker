@extends('layouts.layout')

@section('title')
{{__('Task Detail')}}
@endsection

@section('content')
<div id="status" class="container d-flex">
  <div class="list-group">
    <div class="list-group-item list-group-item-action bg-success text-light"><h3>{{__('In Progress')}}</h3></div>
    <div class="list-group-item list-group-item-action"><h4>{{__('Requested By')}}</h4> <br /> <img src="https://via.placeholder.com/40"
        style="border-radius: 50%;">
      Jane Manager</div>
    <div class="list-group-item list-group-item-action"><h4>{{__('Participants')}}</h4> <br />
      <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
      <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
      <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
      <img src="https://via.placeholder.com/40" style="border-radius: 50%;"></div>
    <div class="list-group-item list-group-item-action"><i class="far fa-calendar-alt fa-lg"></i> {{__('Completed
      On')}}
      <br /> <h4>10/12/18 18:25</h4></div>
  </div>
  <div class="list-group">
    <div class="list-group-item list-group-item-action bg-secondary text-light"><h3>{{__('Completed')}}</h3></div>
    <div class="list-group-item list-group-item-action"><h4>{{__('Requested By')}}</h4> <br /> <img src="https://via.placeholder.com/40"
        style="border-radius: 50%;">
      Jane Manager</div>
    <div class="list-group-item list-group-item-action"><h4>{{__('Participants')}}</h4> <br />
      <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
      <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
      <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
      <img src="https://via.placeholder.com/40" style="border-radius: 50%;"></div>
    <div class="list-group-item list-group-item-action"><i class="far fa-calendar-alt fa-lg"></i> {{__('Completed
      On')}}
      <br /> <h4>10/12/18 18:25</h4></div>
  </div>
  <div class="list-group">
    <div class="list-group-item list-group-item-action bg-danger text-light"><h3>{{__('Error')}}</h></div>
    <div class="list-group-item list-group-item-action"><h4>{{__('Requested By')}}</h4> <br /> <img src="https://via.placeholder.com/40"
        style="border-radius: 50%;">
      Jane Manager</div>
    <div class="list-group-item list-group-item-action"><h4>{{__('Participants')}}</h4> <br />
      <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
      <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
      <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
      <img src="https://via.placeholder.com/40" style="border-radius: 50%;"></div>
    <div class="list-group-item list-group-item-action"><i class="far fa-calendar-alt fa-lg"></i> {{__('Completed
      On')}}
      <br /> <h4>10/12/18 18:25</h4></div>
  </div>

  {{-- <request-status process-id="{{$instance->process->id}}" instance-id="{{$instance->id}}"></request-status> --}}
</div>
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection

@section('js')
<script src="{{mix('js/request/status.js')}}"></script>
@endsection
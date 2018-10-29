@extends('layouts.layout')

@section('title')
{{__('Profile')}}
@endsection

@section('content')
  <div class="container" id="profileForm">
    <h1>{{$user->firstname}} {{$user->lastname}}</h1>
    <div class="row">
      <div class="col-8">
        <div class="card card-body">
          <h4 class="mt-2">{{__('Contact Information')}}</h4>
          <div><i class="fas fa-envelope fa-lg text-secondary pr-1"></i><a href="mailto:{{$user->email}}">{{$user->email}}</a> </div>        
          <div><i class="fas fa-phone fa-lg text-secondary pr-1"></i>{{__('Phone')}}  {{$user->phone}}</div>
          @if ($user->fax)
          <div><i class="fas fa-phone fa-lg text-secondary pr-1"></i>{{__('Fax')}}  {{$user->fax}}</div>
          @endif
          @if ($user->cell)
          <div><i class="fas fa-phone fa-lg text-secondary pr-1"></i>{{__('Cell')}}  {{$user->cell}}</div>
          @endif
          @if($user->address)
          <h4 class="mt-2">{{__('Address')}}</h4>
          <div>{{$user->address}}</div>
          <div>{{$user->city}}, {{$user->state}} </div>
          <div>{{$user->postal}} {{$user->country}}</div>
          @endif
          <h4 class="mt-2">{{__('Current Local Time')}}</h4>
          <div><i class="far fa-calendar-alt fa-lg text-secondary pr-1"></i>{!! Carbon\Carbon::now()->setTimezone($user->timezone)->format('m/d/Y H:i'); !!}</div>        
        </div>
      </div>
      <div class="col-4">
        <div class="card card-body">
          <div align="center" data-toggle="modal" data-target="#exampleModal">
            <img src="https://via.placeholder.com/150x150" style="border-radius: 50%">
             <h1>{{$user->firstname}} {{$user->lastname}}</h1>
             <h4>{{$user->title}}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_designer')])
@endsection
@extends('errors.base')

@section('title')
  {{__('Server Error - ProcessMaker')}}
@endsection

@section('message')
  <h1>{{__('Server Error')}}</h1>
  <p>{{__('Contact your administrator for more information')}}</p>
@endsection


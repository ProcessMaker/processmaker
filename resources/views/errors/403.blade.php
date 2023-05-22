@extends('errors.base')

@section('title')
  {{__('Unauthorized - ProcessMaker')}}
@endsection

@section('message')
  <h1>{{__('Not Authorized')}}</h1>
  <p>{{__('Contact your administrator for more information')}}</p>
@endsection

@extends('errors.base')

@section('title')
  {{__('Page not found - ProcessMaker')}}
@endsection

@section('message')
  <h1>{{__('Oops!')}}</h1>
  <p>{{__('The page you are looking for could not be found')}}</p>
@endsection
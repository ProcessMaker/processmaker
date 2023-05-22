@extends('errors.base')

@section('title')
  {{__('Server Error - ProcessMaker')}}
@endsection

@section('message')
    <h1>{{__('Server Error')}}</h1>
    <p>{{__('One of the data sources in unavailable. Contact with your system administrator.')}}</p>
@endsection


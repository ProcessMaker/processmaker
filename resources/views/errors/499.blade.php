@extends('errors.base')

@section('title')
  {{__('ProcessMaker')}}
@endsection

@section('message')
  <h1>{{__('Oops!')}}</h1>
  <p>{{ $exception->getMessage() }}</p>
@endsection